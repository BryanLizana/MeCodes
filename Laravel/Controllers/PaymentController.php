<?php

namespace App\Modules\Test\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use PayPal\Api\Payer;
// use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use URL;
use Redirect;
use Caffeinated\Flash\Facades\Flash;
use Session;
use Illuminate\Support\Facades\Input;
use PayPal\Api\PaymentExecution;


class PaymentController extends Controller
{
    //
     //
     private  $item_list = null ;
     private  $amount = null ;
     protected  $var_ReturnUrl = null ;
     protected  $var_CancelUrl = null ;
     
     public function __construct()
     {
         // Url repsonse of paypal
        $this->var_ReturnUrl = URL::route('publico::reponse-paypal-return') ;
        $this->var_CancelUrl = URL::route('publico::reponse-paypal-cancel') ;
 
 
         /** PayPal api context **/        
         $paypal_conf = \Config::get('paypal');
 
         $this->_api_context = new \PayPal\Rest\ApiContext(
             new \PayPal\Auth\OAuthTokenCredential(
                 $paypal_conf['client_id'],     // ClientID
                 $paypal_conf['secret']      // ClientSecret
                 )
         );
 
         $this->_api_context->setConfig($paypal_conf['settings']);
 
     }
 
     public function setItems($item_list)
     {
         if (is_array($item_list)  ) {
             $this->item_list = new ItemList();
             $this->item_list->setItems($item_list);
         }    
     }
 
 
     public function setAmount($amount_float)
     {
         if (is_numeric($amount_float)  ) {
             $this->amount = new Amount();
             $this->amount->setCurrency('USD')
                         ->setTotal($amount_float); /// Se debe especificar el total                        
         }    
     }
 
     public function payWithpaypal()
     {
         $payer = new Payer();
         $payer->setPaymentMethod('paypal');
 
         if (empty($this->item_list) || empty($this->amount) ) {
            echo '<pre>'; var_dump( 'No hay producto o Total in  PaymentC.:payWithpaypal()',json_decode($ex->getData()) ); echo '</pre>'; die; /***VAR_DUMP_DIE***/           
         }
    
 
       
         $transaction = new Transaction();
         $transaction->setAmount($this->amount)
                     ->setItemList($this->item_list)
                     ->setDescription('Your transaction description');
 
         $redirect_urls = new RedirectUrls();
         $redirect_urls->setReturnUrl($this->var_ReturnUrl) /** Specify return URL **/
                     ->setCancelUrl($this->var_CancelUrl);
 
         $payment = new Payment();
         $payment->setIntent('Sale')
                     ->setPayer($payer)
                     ->setRedirectUrls($redirect_urls)
                     ->setTransactions(array($transaction));
                 /** dd($payment->create($this->_api_context));exit; **/
 
             
         try {
             
             $payment->create($this->_api_context);
             
         } catch (\PayPal\Exception\PayPalConnectionException $ex) {
             
             if (\Config::get('app.debug')) {
                 // \Session::put('error', 'Connection timeout');
                 // return Redirect::route('paywithpaypal');
                  echo '<pre>'; var_dump( 'Connection timeout in  PaymentC.:payWithpaypal()',json_decode($ex->getData()) ); echo '</pre>'; die; /***VAR_DUMP_DIE***/                
             } else {
                 // \Session::put('error', 'Some error occur, sorry for inconvenient');
                 // return Redirect::route('paywithpaypal');
                 echo '<pre>'; var_dump( 'Some error occur, sorry for inconvenient in  PaymentC.:payWithpaypal()',json_decode($ex->getData()) ); echo '</pre>'; die; /***VAR_DUMP_DIE***/                
             }
         }
 
         //obtiene la url de redirect to paypal
         foreach ($payment->getLinks() as $link) {
             if ($link->getRel() == 'approval_url') {
                  $redirect_url = $link->getHref();
                 break;
             }
         }
         
         /** add payment ID to session **/
         Session::put('paypal_payment_id', $payment->getId());
         
         if (isset($redirect_url)) {
             // return Redirect::away($redirect_url);
             return  array('redirect_to_paypal' => $redirect_url,
                        'paypal_payment_id' => $payment->getId()
                      );
         }
 
         // \Session::put('error', 'Unknown error occurred');
         //     return Redirect::route('paywithpaypal');
         echo '<pre>'; var_dump( 'Unknown error occurred in  PaymentC.:payWithpaypal()' ); echo '</pre>'; die; /***VAR_DUMP_DIE***/
     }
 
     public function getPaymentStatus()
     {
         /** Get the payment ID before session clear **/
         $payment_id = Session::get('paypal_payment_id');
         /** clear the session payment ID **/
         Session::forget('paypal_payment_id');
         if (empty(Input::get('PayerID')) || empty(Input::get('token'))) {
             return  array('paypal_payment_id' => $payment_id, 'result' => 'Empty PayerID or Token');
             die;
         }
 
         $payment = Payment::get($payment_id, $this->_api_context);
         
         $execution = new PaymentExecution();        
         $execution->setPayerId(Input::get('PayerID'));
 
         /**Execute the payment ¡¡¡ NECESITA UN TRY CATcH en caso recarge la página  **/
         $result = $payment->execute($execution, $this->_api_context);
         
         if ($result->getState() == 'approved') {
             //  \Session::put('success', 'Payment success');
             return  array('paypal_payment_id' => $payment_id, 'result' => json_encode($result->getState()));
             die;            
         }
 
         // \Session::put('error', 'Payment failed');
         return  array('paypal_payment_id' => '0', 'result' => json_encode($result->getState()));
         die;        
     }
 
 
}
