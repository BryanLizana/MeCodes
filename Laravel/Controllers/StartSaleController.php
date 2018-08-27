<?php

namespace App\Modules\Test\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

// use App\Modules\Publico\Http\Controllers\PaymentController;
// use PayPal\Api\Item;

use Redirect;
use Caffeinated\Flash\Facades\Flash;
use Session;
use URL;
use DB;
use Mail;


// Stripe
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Charge;
use Stripe\Error\Card;
use Stripe\Error\InvalidRequest;
use Stripe\Error\Authentication;
use Stripe\Error\Permission;
use Stripe\Error\RateLimit;
use Stripe\Error\Api;

class StartSaleController extends Controller
{
    private $UsersServices;

    private $stripeTokenRequest;
    private $codeTransaction;
    private $template_form = 'General';

    private $haveUserBeneficiary;
    private $list_SalesDetails;

    private $Url_Main_Site = 'https://www.test.com/';
    // DB::beginTransaction();
    // DB::Commit();
    // DB::rollBack();

    private $status_Lead = array('null',
    'Lead Created',
    'Validation',
    'Validation | Start Buy',
    'Start Stripe process',
    'Validation Update',
    'Success Buy | But error',
    'Success Buy',
    );

    private function cartLogError($title,$description,$data)
    {
        $errorLog['title'] = $title;
        $errorLog['description'] = $description;
        $errorLog['data'] = json_encode($data);
        CartLog::create($errorLog);
    }

    private function fileGetContentsBroker($filename, $use_include_path = true, $context = null)
    {
        if (strpos($filename, 'api/broker')) {
            $filename .= '?user_name=broker_user&api_key=yQE6k96PfuU92RctDn5dR3DYZXQ7LSvbsmjecrJ7kyS6f9nu7s';
        }

        $response = file_get_contents($filename, $use_include_path, $context) ;
        if ($response == 'none') {
            return json_encode(array('error' => 'Fail request Broker' ));
        }
        return $response;
    }

    public function preInit()
    {       
        if (!empty(Session::get('code_transaction'))) { //si ya se completó enviar directo a la url de redirección
            return Redirect::route('publico::success-buy', array('code_transaction' => Session::get('code_transaction') ));
        }


        $productSelected = Session::get('productSelected');
        if (empty($productSelected) || empty(Session::get('id_product'))  || empty(Session::get('zip_code'))) {
            return redirect($this->Url_Main_Site);
        }
        $this->template_form = $productSelected->template_form;  //especificar el template de formulario

        $datos_request_old = json_decode(json_encode(Session::get('data')), true);  //la session data se guarda como object        

        if (empty($datos_request_old) || !is_array($datos_request_old) ) {
            Flash::error('Fail request');
            return false;
        }
        
        try {
            // Registrar datos en el sistema
            // DB::beginTransaction();
            
            $this->codeTransaction = StartSaleServices::generateCodeTransaction();
            // $this->stripeTokenRequest = $request->stripeToken;

            $form_values = $datos_request_old ; //No hay datos que recojer del último paso
           
            $form_values['user_address'][0]['address'] = $form_values['user_address']['address'];
            $form_values['user_address'][0]['city'] = $form_values['user_address']['city'];
            $form_values['user_address'][0]['state'] = $form_values['user_address']['state'];
            $form_values['user_address'][0]['zip_code'] = $form_values['user_address']['zip_code'];
            $address_uno = $form_values['user_address'][0];            
            $form_values['user_address'] = null;  // Eliminar datos extras de address:
            $form_values['user_address'][0] = $address_uno;
                
            if ( isset( $form_values['have_mailing_address']) &&  $form_values['have_mailing_address'] == '1') {
                $form_values['user_address'][1]['address'] = $form_values['mailing_address'];
                $form_values['user_address'][1]['city'] = $form_values['mailing_city'];
                $form_values['user_address'][1]['state'] = $form_values['mailing_state'];
                $form_values['user_address'][1]['zip_code'] = $form_values['mailing_zip_code'];
                $address_dos = $form_values['user_address'][1];
            }
            
            // Add/Update user_client
            $this->UsersServices =  new UsersServices();
            $response_user =  $this->UsersServices->store($form_values);
            if (isset($response_user['error'])) {
                Flash::error('User Error:'.$response_user['error']);
                return false;
            }

            $user = $response_user['data'];
            if (isset($user['user_address']['error'])) {
                Flash::error('user Address Error:'. $user['user_address']['error']);
                return false;
            }
            
            $zip_code = Session::get('zip_code');
            if (!isset($zip_code) || !is_numeric($zip_code) || $zip_code < 1 ) {
                Flash::error('ZipCode Error:'.$zip_code);
                return false;
            }
            
            $url = "http://www.test.com/thor/get/".$zip_code;
            $context = stream_context_create(array(
                'http' => array(
                    'ignore_errors'=>true,
                    'method'=>'GET'
                    // for more options check http://www.php.net/manual/en/context.http.php
                )
            ));
            
            $state = json_decode(file_get_contents($url, false, $context));
            if (!isset($state->data->StateCode)) {
                Flash::error('Error:ZipCode:'.$zip_code.' no valid');
                return false;
            }

            $state_code = $state->data->StateCode;            
            // $productSelected = Session::get('productSelected');// se llama arriba
            
            if (!is_numeric($productSelected->id) ||  $productSelected->id < 1 || empty(trim($productSelected->id))) {            
                Flash::error('Error: There has not been any product');
                return false;
            }

            $id_product = trim($productSelected->id);

            $list_items = json_decode(self::fileGetContentsBroker(URL::to('/').'/api/broker/product_cost/list/filter_by/state/'.$state_code.'/product/'.$id_product), false);
            if (isset($list_items->error)) {
                Flash::error('Error: '.$list_items->error .'.');
                return false;
            }
            $list_items = $list_items->data;
            //total a pagar
            $sub_total = 0;
            $i=0; //contador para lso productos (Sólo es uno, por ahora)

            // Detail Items
            $this->list_SalesDetails[$i]['name'] = $list_items->name;
            $this->list_SalesDetails[$i]['price'] = $list_items->price;
            $this->list_SalesDetails[$i]['quantity'] = 1;
            $this->list_SalesDetails[$i]['id_product'] = $list_items->id;
            $sub_total += $list_items->price;
            // $i++;
            
            $total_cost = $sub_total;
            $code_coupon = '';
            $coupon_ammount = 0;
            $coupon_type = '';

            $form_values['sub_total'] = $sub_total;
            $form_values['total'] = $total_cost;

            $form_values['ip'] ='';
            $form_values['id_user'] = $user['id'];  //Buyer
            $form_values['code_coupon'] =  $code_coupon ;
            $form_values['coupon_ammount'] =  $coupon_ammount ;
            $form_values['coupon_type'] = $coupon_type ;
            
            $form_values['address'] = $form_values['user_address'][0]['address'];
            $form_values['city'] =  $form_values['user_address'][0]['city'];
            $form_values['state'] =  $form_values['user_address'][0]['state'];
            $form_values['zip_code'] =  $form_values['user_address'][0]['zip_code'];  
            
            if (!isset($form_values['have_mailing_address']) || $form_values['have_mailing_address'] == 0) {
                $form_values['mailing_address'] = "";
                $form_values['mailing_city'] = "";
                $form_values['mailing_state'] = "";
                $form_values['mailing_zip_code'] = "";
            }                
            
            $form_values['process_name'] = $this->status_Lead[1]; 
            $form_values['last_error'] = '';
            $form_values['status'] = 0; //la compra aún no se finaliza

            if (!empty(Session::get('cartId'))) {
                $sale = Cart::find(Session::get('cartId'));            
                foreach ($form_values as $key => $value) {
                    if (isset($sale->$key)) {
                        $sale->$key = $value;
                    }
                }

                $sale->save();
            }else {
                $form_values['code_transaction'] = $this->codeTransaction;
                $sale = Cart::create($form_values);            
            }        
            $this->cartId = $sale->id;

            $form_values['user_beneficiary'] =  array_filter($form_values['user_beneficiary'], "strlen");
            if (isset($form_values['user_beneficiary']) && isset($form_values['have_user_beneficiary']) && $form_values['have_user_beneficiary'] == 1) {                    
                // Add user beneficiary
                $user_beneficiary = $this->UsersServices->store($form_values['user_beneficiary']);
                if (isset($user_beneficiary['error'])) {
                    $sale->last_error = 'Error in Add User beneficiary:'. $user_beneficiary['error'] ;
                    $sale->save();

                    Flash::error('Error in Add User beneficiary:'. $user_beneficiary['error']);
                    return false;
                }
                $id_user_beneficiary = $user_beneficiary['data']['id'];
                $user_beneficiary = $user_beneficiary['data'];
                $this->haveUserBeneficiary = 'YES';
            } else {
                $id_user_beneficiary = $user['id']; //me self
                $user_beneficiary = $user;
                $this->haveUserBeneficiary = 'NO';               
            }
            
            $user_beneficiary = json_decode(json_encode($user_beneficiary), true);
            $vars['id_user'] = $id_user_beneficiary;
            $vars['id_cart'] = $this->cartId ;
            $vars = array_merge($vars, $user_beneficiary);
            $vars['relationship'] = (!empty($form_values['user_beneficiary']['relationship'])  && $this->haveUserBeneficiary == "YES") ? $form_values['user_beneficiary']['relationship'] : 'Me Self';
            
            $CartUserBeneficiary_to_delete = CartUserBeneficiary::where('id_cart',  $this->cartId )->get(); //para eliminar los beneficiarios anteriores (En el caso de que el usuario avanze,
            if (!$CartUserBeneficiary_to_delete->isEmpty()) {                                               // registre, y luego retroceda y otra vez avance con nuevos cambios)
                foreach ($CartUserBeneficiary_to_delete as $row) {
                    $row->delete();
                }
            }

            $CartUserBeneficiary = CartUserBeneficiary::create($vars);

            if (isset($this->cartId) && count($this->list_SalesDetails) > 0) {

                $CartItems_to_delete = CartItems::where('id_cart',  $this->cartId )->get(); //eliminar datos anteriores (usuario: avanza y retrocede y otra vez avanza con nuevos datos)
                if (!$CartItems_to_delete->isEmpty()) {
                    foreach ($CartItems_to_delete as $row) {
                        $row->delete();
                    }
                }

                foreach ($this->list_SalesDetails as $SaleDetails) {
                    $SaleDetails['id_cart'] = $this->cartId ;
                    $SaleDetailsInsert = CartItems::create($SaleDetails);
                }
            } else {
                
                if ($sale) {
                    $sale->last_error =  'Cart Id not found' ;
                    $sale->save();
                }

                self::cartLogError('Cart Id not found','Cart Id not found',$this->cartId);
                Flash::error('Error: Please try again later');
                return false;
            }                                  

        } catch (\Illuminate\Database\QueryException $ex) {
            // DB::rollBack();
            if ($sale) {
                $sale->last_error =  'Insert Sale:'. $ex->getMessage() ;
                $sale->save();
            }

            Flash::error('Insert Sale:'. $ex->getMessage());
            self::cartLogError('QueryException in Insert Sale','Insert Sale:'. $ex->getMessage(),$form_values);
            return false;
        }
        // DB::Commit();
        
        Session::put('cartId', $sale->id);  //Pre Compra validada, ya se puede usar los datos          

        $respuesta = ''                     ;
        //Response error NSD
        if ($sale) {
            $sale->process_name = $this->status_Lead[2]; 
            $sale->last_error =  ( isset($respuesta['error']) ) ? $respuesta['message'] : '' ;
            $sale->save();
        }

        return $respuesta;
    }

    public function init(Request $request)
    {
        if (!empty(Session::get('code_transaction'))) { //si ya se completó enviar directo a la url de redirección
            return Redirect::route('publico::success-buy', array('code_transaction' => Session::get('code_transaction') ));
        }

        if (empty(Session::get('cartId'))) {
            return redirect($this->Url_Main_Site);
        }


        $productSelected = Session::get('productSelected');
        if (empty($productSelected) || empty(Session::get('id_product'))  || empty(Session::get('zip_code'))) {
            return redirect($this->Url_Main_Site);
        }

        $this->template_form = $productSelected->template_form;  //especificar el template para tener vistas de cada tipo de seguro y no ir a la genérica

        $datos_request_old = json_decode(json_encode(Session::get('data')), true);  //la session data se guarda como object
        if (!isset($request->stripeToken) || empty($request->stripeToken) ) {            
            Flash::error('Fail request Token');
            return Redirect::to(URL::previous() . "#step_main");
        }

        if (empty($datos_request_old) || !is_array($datos_request_old) ) {
            Flash::error('Fail request');
            return Redirect::to(URL::previous() . "#step_main");
        }
        
        if (empty(Session::get('validate_nsd_request'))) { //TODO: se reemplaza por la session de cartID
            return Redirect::to(URL::previous() . "#step_main");
        }
        
        try {
            // Registrar datos en el sistema
            // DB::beginTransaction();
            
            // $this->codeTransaction = StartSaleServices::generateCodeTransaction();
            $this->stripeTokenRequest = $request->stripeToken;

            $form_values = $datos_request_old ; //No hay datos que recojer del último paso
            
            $saleEdit = Cart::find(Session::get('cartId'));
            if (!$saleEdit) {
                Flash::error('User Error: Cart ID not found');
                return Redirect::to(URL::previous() . "#step_main");
            }

            $saleEdit->process_name =  $this->status_Lead[3] ;
            $saleEdit->last_error = '' ;
            $saleEdit->ip = $request->ip();
            $saleEdit->save();   
    

            $this->cartId = $saleEdit->id;
            
        } catch (\Illuminate\Database\QueryException $ex) {
            // DB::rollBack();
            if (!$saleEdit) {
                $saleEdit->last_error = 'Insert Sale:'. $ex->getMessage();
                $sale->save();
            }
            Flash::error('Insert Sale:'. $ex->getMessage());
            self::cartLogError('QueryException in Insert Sale','Insert Sale:'. $ex->getMessage(),$form_values);
            return Redirect::to(URL::previous() . "#step_main");
        }
        // DB::Commit();
        return self::stripeProcess(); // Start of end process
    }

    private function stripeProcess()
    {
        $cartId = $this->cartId;
        $code_transaction = Session::get('code_transaction'); //tiene que ser null en cada compra nueva
        if (!empty($this->stripeTokenRequest) && !empty($cartId) && is_numeric($cartId) && $cartId > 0 && empty($code_transaction)) {
            //Proceso de Stripe, start¡¡
            try {
                $saleEdit = Cart::find($cartId);
                if ($saleEdit) {
                    $saleEdit->process_name =  $this->status_Lead[4] ;
                    $saleEdit->last_error = '' ;
                    $saleEdit->save(); 

                    // parametros requeridos:
                    $form_values = json_decode(json_encode(Session::get('data')), true);
                    $email_cliente = $form_values['email'];
                    $stripeToken = $this->stripeTokenRequest;
                    $total_cost = $saleEdit->total;
                    
                    Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

                    //FIXME: Añadir datos del usuario relacionado a la tarjeta- que no es necesariamente el usuario beneficiario o el principal del request                
                    $customer = Customer::create(array(
                        'email' =>  $email_cliente,
                        'source' => $stripeToken,
                        'metadata' => array('first_name' => $form_values['first_name'],'last_name' =>$form_values['last_name'],'user_beneficiary'=>$this->haveUserBeneficiary)
                    ));
                    
                    $description = array();

                    $CartItems = CartItems::where('id_cart',$this->cartId)->get();

                    if ($CartItems->isEmpty()) {
                        $errorLog_data = $this->cartId;
                        self::cartLogError('CartItems isEmpty','CartItems isEmpty',$errorLog_data);                                   
                        return redirect($this->Url_Main_Site);
                    }

                    foreach ($CartItems as $product) {
                        $description[] = $product->name;
                        $LessQuantityProductTo[] = $product->id_product; //disminuir el stock
                    }

                    $description = implode(' , ', $description);
                    $description = "Compra de: $description";

                    $data_send_to_stripe['order_id'] =  $cartId ;
                    $data_send_to_stripe['code_transaction'] = $saleEdit->code_transaction ;
                    $data_send_to_stripe['first_name'] = $form_values['first_name'] ;
                    $data_send_to_stripe['last_name'] = $form_values['last_name'] ;
                    $data_send_to_stripe['user_beneficiary'] =  'no';

                    if (isset($form_values['have_user_beneficiary']) && $form_values['have_user_beneficiary'] == '1') {
                        $data_send_to_stripe['user_beneficiary'] =  'yes';
                        $data_send_to_stripe['user_beneficiary_first_name'] = $form_values['user_beneficiary']['first_name'] ;
                        $data_send_to_stripe['user_beneficiary_last_name'] =  $form_values['user_beneficiary']['last_name'] ;
                    }

                    // $data_send_to_stripe['first_name'] = $form_values['first_name'] ;
                    
                    $charge = Charge::create(array(
                        'customer' => $customer->id,
                        'amount' =>  $total_cost * 100,
                        'currency' => 'usd',
                        'description' => $description,
                        //FIXME: añadir más datos de la compra
                        'metadata' =>  $data_send_to_stripe
                    ));
                    // Si la tarjeta tiene problemas no pasa de aquí, el Exception de stripe se activa.
                    
                    Session::put('code_transaction', $saleEdit->code_transaction);  

                    //indicar que el proceso ha culminado y evitar  que el usuario ejecute esta función más de una vez en un sólo proceso de compra
                    $this->stripeTokenRequest =  null;
                    $this->codeTransaction =  null;
                    $this->haveUserBeneficiary = null;

                    // 'json_encode($charge)'; https://stripe.com/docs/api#charge_object
                    $saleEdit->paid_code = $charge->id;
                    // $saleEdit->paid_status = $charge->status;
                    $saleEdit->status = 1;  //proceso de compra completado

                    Session::put('data', null); //ya los datos se han registrado y el proceso ha finalizado
                    Session::put('productSelected', null);
                    Session::put('validate_nsd_request', null);

                    Session::put('zip_code', null);
                    Session::put('id_product', null);
                    
                    if ($charge->status == "succeeded") {
                                                       
                        $form_values['code_transaction'] = Session::get('code_transaction');

                        $saleEdit->process_name =  $this->status_Lead[5] ;
                        $saleEdit->card_last4 = $charge->source->last4;
                        $saleEdit->card_brand = $charge->source->brand;
                        $saleEdit->template_form = $this->template_form;
                        $saleEdit->save(); 

                        // Session::put('start_buy',null);
                        foreach ($LessQuantityProductTo as $id_pr) { //multi productos, por ahora sólo es de uno 13-07-2018
                            ProductsInventoryServices::confirmarCompra($id_pr); //reducir stock real
                        }

                        $response_nsd_jarvis = ''; //self::saveLeadNsd($form_values);

                        if (isset($response_nsd_jarvis['error']) ) {
                            $saleEdit->process_name =  $this->status_Lead[6] ;
                            $saleEdit->last_error =  $response_nsd_jarvis['message'];
                            $saleEdit->save(); 
                        }else {
                            $saleEdit->url_file_nsd =  $response_nsd_jarvis['message'] ; //file pdf
                            $saleEdit->process_name =  $this->status_Lead[7] ; //success Real
                            $saleEdit->save(); 
                        }                                                                

                        return Redirect::route('publico::success-buy', array('code_transaction' => Session::get('code_transaction') ));
                   
                    }else {
                        $saleEdit->last_error = 'Response from the server stripe not found:'. json_encode($charge) ;
                        $saleEdit->save();
                        Session::put('code_transaction', null);
                        self::cartLogError('Response from the server stripe','Response from the server stripe',$charge);
                        Flash::error('Error: Please try again later');
                        return Redirect::to(URL::previous() . "#step_main");

                    }                    
                } else {
                    Session::put('code_transaction', null);
                    self::cartLogError('Cart Id not found','Cart Id not found',$cartId);
                    Flash::error('Error: Please try again later');
                    return Redirect::to(URL::previous() . "#step_main");
                }
            } catch (InvalidRequest $ex) {

                Session::put('code_transaction', null);
                if ($saleEdit) {
                    $saleEdit->last_error = 'InvalidRequest Exception'. $ex->getMessage() ;
                    $saleEdit->save();
                }
                Flash::error($ex->getMessage());
                return Redirect::to(URL::previous() . "#step_main");

            } catch (Card $ex) {

                Session::put('code_transaction', null);
                if ($saleEdit) {
                    $saleEdit->last_error = 'Card Exception'. $ex->getMessage() ;
                    $saleEdit->save();
                }

                Flash::error($ex->getMessage());
                return Redirect::to(URL::previous() . "#step_main");                

            } catch (\Illuminate\Database\QueryException $ex) {
                
                Session::put('code_transaction', null);
                if ($saleEdit) {
                    $saleEdit->last_error = 'QueryException Exception'. $ex->getMessage() ;
                    $saleEdit->save();
                }
                self::cartLogError('QueryException in Stripe Process',$ex->getMessage(),$cartId);
                Flash::error($ex->getMessage());
                return Redirect::to(URL::previous() . "#step_main");
            }
        } else {
            $errorLog_data = json_encode([!empty($this->stripeTokenRequest) , !empty($cartId) , is_numeric($cartId) ,($cartId > 0) , empty($code_transaction)]);
            self::cartLogError('Validate fail of Start Stripe Process','Validate fail of Start Stripe Process',$errorLog_data);
                       
            Flash::error('Start Stripe Process is Fail');
            return Redirect::to(URL::previous() . "#step_main");
        }
    }

    public function success($code_transaction)
    {
        $data = Cart::where('code_transaction', $code_transaction)->first();  
        if ($data) {
            $user = CartUserBeneficiary::where('id_cart', $data->id)->first();
            $template_form = (empty($data->template_form)) ? $this->template_form : $data->template_form;
        
            Session::put('code_transaction', null);  //una vez ingresado aquí ya el proceso finaliza y queda libre de registrar otra compra
            Session::put('cartId', null);

            $data_view['data_full'] =  $data;
            $data_view['user_beneficiary'] =  $user;
            $data_view['url_file_nsd'] = (!empty($data->url_file_nsd)) ? URL::to('/') . '/products/files/'.$data->url_file_nsd.'.pdf' : '';

            if (strpos($data->process_name,"uccess Buy") && !strpos($data->process_name," - Success confirmed")) {
                $temp = $data->process_name;
                $data->process_name =  $temp .' - Success confirmed' ;
                $data->save();

                Mail::send("publico::Forms.$template_form.success-to-email", ['data_full' => $data_view['data_full'], 'user_beneficiary' => $data_view['user_beneficiary'],'url_file_nsd'=> $data->url_file_nsd], function ($m) use ($data) {
                    $m->from('info@confielms.com', 'Your Application');    
                    $m->to($data->email, 'User Client')->subject('Your Reminder!');
                });
                                
            }

            return view("publico::Forms.$template_form.success", $data_view);  
                  
        } else {
            die('Hola :) - datos no encontrados');
        }
    }

}
