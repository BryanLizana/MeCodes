<?php

namespace App\Modules\Test\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use App\Modules\Publico\Services\ProductServices;


use Redirect;
use Caffeinated\Flash\Facades\Flash;
use Session;
use URL;

use App\Modules\Publico\Http\Controllers\StartSaleController;


class PublicoController extends Controller
{
    //
    private $ProductServices;
    private $Url_Main_Site = 'https://www.freewayinsurance.com/';


    public function __construct() {
        $this->ProductServices = new ProductServices();
    }

    private function fileGetContentsBroker($filename, $use_include_path = true, $context = null)
    {
        if (strpos($filename, 'api/broker')) {
            $filename .= '?user_name=broker_user&api_key=yQE6k96PfuU92RctDn5dR3DYZXQ7LSvbsmjecrJ7kyS6f9nu7s';
        }
        $response = file_get_contents($filename, $use_include_path, $context) ;
        if ($response == 'none') {
            return json_encode(array('error' => 'Fail request' ));
        }
        return $response;
    }

    public function tempWebsite()
    {
        return view('publico::website');
    }

    public function cartLogError($title = "",$description = "",$data = "")
    {
        $errorLog['title'] = $title;
        $errorLog['description'] = $description;
        $errorLog['data'] = json_encode($data);
        CartLog::create($errorLog);
    }

    public function viewCategory($category)
    {
        $CategoryServices = new CategoryServices();
        $respuesta = $CategoryServices->getDataValid($category);
        if (isset($respuesta['error'])) {
            Flash::error($respuesta['message']);
            return view('publico::400'); 
            // return redirect($this->Url_Main_Site);
        } else {
            $data = $respuesta['data'];
            return view("publico::category", $data);
        }
    }

    public function viewProduct( $product)
    {
        $respuesta = $this->ProductServices->getDataValid($product);
        if (isset($respuesta['error'])) {
            Flash::error($respuesta['message']);
            return redirect($this->Url_Main_Site);
        } else {
            $data = $respuesta['data'];    
            $product = $data['product'];
            return redirect($product->product_external_url);
            // return view("publico::producto", $data);
        }
    }

    public function nextForm(Request $request)
    {
        // Inicio del formulario (transición del Quote-ZipCode al formulario-cliente)
        $form_values = $request->all();        
        if (isset($form_values['slug'])) {
            // Obtener el id_product from slug
            $respuesta = $this->ProductServices->checkSlug($form_values['slug']);
            if (isset($respuesta['error'])) {
                Flash::error($respuesta['message']);
                return view('publico::400'); 
                // return redirect($this->Url_Main_Site); 
            }
            $data = $respuesta['data'];
            $form_values['id_product'] = $data['id'];
        }

        if (!isset($form_values['zip_code']) || !isset($form_values['id_product'])  ) {
            if (!empty($data['product_external_url'])) {
                return redirect($data['product_external_url']);
            }
            return redirect($this->Url_Main_Site);
        }

        // Eliminar las variables de sessiones usadas en cada proceso de compra
        Session::put('cartId', null);
        Session::put('data', null);
        Session::put('code_transaction', null);
        Session::put('validate_nsd_request', null);
        // Variables importantes - Necesarias
        Session::put('zip_code', $form_values['zip_code']);
        Session::put('id_product', $form_values['id_product']);
                                
        $dataProduct  = self::setValuesProductSelected(); //hay producto disponible?
        if (!$dataProduct) {
            self::cartLogError('nextForm::setValuesProductSelected','nextForm::setValuesProductSelected - Error no found',$form_values);
            if (!empty($data['product_external_url'])) {
                return redirect($data['product_external_url']);
            }
            return redirect($this->Url_Main_Site);
        }
        
        $this->view_data['product'] =  $dataProduct->slug;
        $this->view_data['step'] = 'step-1';
        Session::put('productSelected', $dataProduct);
        return Redirect::route('publico::product-step', $this->view_data);
        
    }

    public function stepProduct($product, $step)
    {
        $respuesta = $this->ProductServices->getDataValid($product);
        if (isset($respuesta['error'])) {
            Flash::error($respuesta['message']);
            return redirect($this->Url_Main_Site);
        }

        $productSelected = Session::get('productSelected');
        if (empty($productSelected) || empty(Session::get('id_product'))  || empty(Session::get('zip_code'))) {            
            $product_valid = $respuesta['data']['product'];
            $url_redirect = (isset($product_valid->product_external_url)) ? $product_valid->product_external_url :$this->Url_Main_Site; 
            return redirect($url_redirect);
        }

        // Cuando un usuario está comprando un producto y va a otro producto (por medio de url directa), no se debe permitir 
        if ($product != $productSelected->slug ) {
            Flash::error('Error: Not Valid url');
            return redirect($this->Url_Main_Site);
        }

        // Set city and State
        $url = "http://www.test.com/thor/get/".Session::get('zip_code');
        $context = stream_context_create(array(
            'http' => array(
                'ignore_errors'=>true,
                'method'=>'GET'
                // for more options check http://www.php.net/manual/en/context.http.php
        )));
        
        $state = json_decode(file_get_contents($url, false, $context));
        if (isset($state->data->StateCode)) {
            $data_view["city"] = $state->data->City;
            $data_view["state"] = $state->data->StateCode;
        }

        $data_view['productSelected'] = $productSelected;
        $template_form = $productSelected->template_form; //form folder
        $data_view['step'] = $step;        
        $data_view['zip_code'] = Session::get('zip_code');
        $data_view['full_data_cliente'] = Session::get('data');
        $data_view['step_back'] = route('publico::product', array('product' =>  $product ));
        // $data_view['users_address_list'] = json_decode(json_encode(array('only' => 'one only' ))) ; //para listar un input file vacío
        
        $view = "publico::Forms.$template_form.$step" ;
        if (view()->exists($view)) {
            return view($view, $data_view);                       
        } else {
            Flash::error('View no found:'.$step);
            return redirect($this->Url_Main_Site);
        }
    }

    public function stepProductPost( $product, $step, Request $request)
    {
        // Cambio de zip_code 
        $old_zipcode = Session::get('zip_code');

        // Next step
        $datos_request_old = json_decode(json_encode(Session::get('data')), true);
        $datos_request_new = $request->All();

        // La validación del zipcode en el front sólo funciona de una manera, por ello se realiza el next | parche - 30-07 - 2018
        if (isset($datos_request_new['zipcode']) && !empty($datos_request_new['zipcode'])) {
            $datos_request_new['user_address']['zip_code']  = $datos_request_new['zipcode'];
            Session::put('zip_code', $datos_request_new['user_address']['zip_code']); 
        }

        // La validación del zipcode-beneficiary en el front sólo funciona de una manera, por ello se realiza el next | parche - 30-07 - 2018
        if (isset($datos_request_new['beneficiary-zipcode'])) {
            $datos_request_new['mailing_zip_code']  = $datos_request_new['beneficiary-zipcode'];
        }

        // Not duplicate email
        if (isset($datos_request_new['have_user_beneficiary']) && $datos_request_new['have_user_beneficiary'] == '1' && isset($datos_request_new['user_beneficiary']['email']) && $datos_request_new['user_beneficiary']['email'] == $datos_request_new['email']) {                   
            Flash::error("This email ".$datos_request_new['user_beneficiary']['email']." it should not be the same as the main customer");  
            Session::put('data', json_decode(json_encode($datos_request_new), false)); // Convertir a Objeto
            return Redirect::to(URL::previous() . "#step_main");
        }

        // Si ya no deseas un beneficiario o un address mailling, set value de have_email or _mailling a 0
        if (!isset($datos_request_new['have_user_beneficiary']) && isset($datos_request_new['have_user_beneficiary_confirmed'])  ) {
            $datos_request_new['have_user_beneficiary'] = 0;
        }
        
        if (!isset($datos_request_new['have_mailing_address']) && isset($datos_request_new['have_mailing_address_confirmed']) ) {
            $datos_request_new['have_mailing_address'] = 0;
        }

        $datos_request = (is_array($datos_request_old)) ? array_merge($datos_request_old, $datos_request_new) : $datos_request_new  ;
        
        // Paso final:
        if (isset($datos_request_new['Pr_No']) && !empty($datos_request_new['Pr_No'])) { //validar el campo del form, no de la session
           
            $response_StartSaleServices = StartSaleServices::validateData($datos_request);
            // FIXME: Mejorar repsuesta, que sea un array de error ,status, data
            if (is_array($response_StartSaleServices)) { //los datos están validados y campos rellenados (State and city)
                $datos_request = $response_StartSaleServices; 
            }else if (is_string($response_StartSaleServices)) { //Los errores son enviados como string 
                Flash::error($response_StartSaleServices);  
                return Redirect::to(URL::previous() . "#step_main");
            }

            $StartSaleController =  new StartSaleController();
            Session::put('data', json_decode(json_encode($datos_request), false)); // Convertir a Objeto | Preparar para exe preInit

            $response_StartSaleController= $StartSaleController->preInit();
            if (!$response_StartSaleController) {
                // Session::put('data', json_decode(json_encode($datos_request_old), false)); // Convertir a Objeto || 
                // Message flash set in preInit()
                return Redirect::to(URL::previous() . "#step_main");
            }

            if (isset($response_StartSaleController['error'])) {
                //Response error NSD
                if ($response_StartSaleController['code'] == "not_valid_request") {
                    Flash::error($response_StartSaleController['message']);                   
                    return Redirect::to(URL::previous() . "#step_main");
                } else if($response_StartSaleController['code'] == "master_id_exist" ) {
                    Flash::error($response_StartSaleController['message']);
                    return Redirect::route('publico::already-insured');//Ya tiene el seguro
                }else {
                    self::cartLogError('NSD - Error no found','NSD - Error no found',$response_StartSaleController);
                    // Flash::error($response_StartSaleController['message']);
                    return Redirect::route('publico::error-500');//Error desconocido
                }
            
            }else {
                Session::put('validate_nsd_request', 'success');
            }
        } //end  Pr_No - proccess

        // Save all data in each step
        Session::put('data', json_decode(json_encode($datos_request), false)); // Convertir a Objeto
        
        // Verificar en cada paso los valores del producto seleccionado
        $dataProduct = self::setValuesProductSelected();
        if (!$dataProduct) {
            return Redirect::to(URL::previous() . "#step_main");
        }
        Session::put('productSelected', $dataProduct);
        
        if ($old_zipcode != Session::get('zip_code')) {
            Flash::error('The zip code has changed ('.$old_zipcode.' => '. Session::get('zip_code').') , so you may have also changed the initial price of the product.');
        }

        // redirect to next page
        return Redirect::route('publico::product-step', array('product'=>$product,'step' => $step ));
    }

    // Obtener los valores del producto dependiendo del zip y producto
    private function setValuesProductSelected()
    {
        $zipcode = Session::get('zip_code');
        $id_product = Session::get('id_product');
        if (empty($zipcode) || empty($id_product)) {
            Flash::error('Zipcode or Product is empty');
            return false;
        }

        $productSelected  = json_decode(self::fileGetContentsBroker(URL::to('/').'/api/broker/product_cost/list/filter_by/zipcode/'.$zipcode.'/product/'.$id_product));
        if (isset($productSelected->error)) {
            Flash::error('Error: '.$productSelected->message .'.');
            return false;
        }

        $productSelected->data->price = number_format($productSelected->data->price, 2, '.', ',');
        // Aplicate Coupon -  preview
        // TODO: Proceso de Coupons deshabilitado
        // $form_values = Session::get('data');
        // if (isset($form_values->code_coupon) && !empty($form_values->code_coupon)) {      
        //     $productSelected  =  self::aplicateCoupon($form_values->code_coupon,$productSelected );
        // }
        return $productSelected->data;
    }

    // TODO: Proceso de Coupons deshabilitado
    // private function aplicateCoupon($code_coupon, $productSelected)
    // {       
    //     $CouponsServices = new CouponsServices();
    //     $response =  $CouponsServices->getCoupons($code_coupon,$productSelected->data->price);
    //     if (isset($response['error'])) {
    //         Flash::error('Error: Coupon no Valid:'.$response['message']);
    //     }else {
    //         $response = $response['data'];
    //         // coupon_title
    //         // coupon_message
    //         // coupon_code
    //         // coupon_type
    //         // coupon_ammount
    //         // descuento_ammount
    //         // price_subtotal
    //         // price_total
    //         $productSelected->data->price = $response['price_total'];
    //         $productSelected->data->code_coupon =  $response['price_total'];
    //         $productSelected->data->coupon_message =  $response['coupon_message'];
    //     }   
    //     return $productSelected;
    // }

    public function alreadyInsured()
    {
        // already-insured
        return view('publico::already-insured');      
    }

    public function errorServer()
    {
        return view('publico::500');      
    }
    
    public function errorPageNotFound()
    {
        return view('publico::400');      
    }
}
