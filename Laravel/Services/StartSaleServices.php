<?php

namespace App\Modules\Publico\Services;

use URL;
use Validator;

class StartSaleServices
{
    // DB::beginTransaction();
    // DB::Commit();
    // DB::rollBack();

    public function respuesta($status, $message, $data = null)
    {
        $message = (!empty($message)) ? $message : "No response";
        if ($status === 1) {
            return  array('status' => 'success','message' => $message,'data' => $data);
        } else {
            return  array('status' => 'error','message' => $message,'error' => 1,'data' => $data);
        }
    }

    static function generateCodeTransaction()
    {
        $key="";
        $caracteres_to_us = "1234567890ABCDEFGHIJKLMNOPQRSTUVXYZ";
        for ($i=0; $i < 15; $i++) {
            $key .= $caracteres_to_us[rand(0, strlen($caracteres_to_us) -1)];
        }
        return "T".$key.date('d');
    }

    static function validateData($Data_or_field)
    {
        $validate_tools = [
            'first_name' => 'required|max:255',     
            'last_name' => 'required|max:255',      
            'email' => 'required|max:255|email',          
            'gender' => ['required','max:6',function($attribute, $value, $fail)
            {
                if ($value != 'male' && $value != 'female') {
                    return $fail($attribute.' is invalid.');
                }
            }],
            'phone' => 'required|max:12', 
            'emails_consents' => 'max:1',
            'phone_consents' => 'max:1',
            'user_address.address' => 'required|max:250', 
            'user_address.city' => 'required|max:250', 
            'user_address.state' => 'required|max:250', 
            'user_address.zip_code' => 'required|max:5',
        ];

        if (isset($Data_or_field['have_user_beneficiary']) && $Data_or_field['have_user_beneficiary'] == 1  ) {            

            $validate_tools_extra = array(
                'user_beneficiary.first_name' => 'required|max:255',     
                'user_beneficiary.last_name' => 'required|max:255',      
                'user_beneficiary.email' => 'required|max:255|email',          
                'user_beneficiary.gender' => ['required','max:6',function($attribute, $value, $fail)
                {
                    if ($value != 'male' && $value != 'female') {
                        return $fail($attribute.' is invalid.');
                    }
                }],
                'user_beneficiary.phone' => 'required|max:12', 
                'user_beneficiary.emails_consents' => 'max:1',
                'user_beneficiary.phone_consents' => 'max:1',              
            );
            $validate_tools =  array_merge($validate_tools, $validate_tools_extra);
        }

        if (isset($Data_or_field['have_mailing_address']) && $Data_or_field['have_mailing_address'] == 1  ) {
            $validate_tools_extra = array(
                'mailing_address' => 'required|max:250', 
                'mailing_city' => 'required|max:250', 
                'mailing_state' => 'required|max:250', 
                'mailing_zip_code' => 'required|max:5',            
            );
            $validate_tools =  array_merge($validate_tools, $validate_tools_extra);
        }

       if (is_array($Data_or_field)) { //Siempre tiene que ser array:

           //sólo los fields que vienen en $Data_or_field se validarán, caso contrario todos según validate_tools
           if (isset($Data_or_field['typeValidate']) && !empty($Data_or_field['typeValidate']) && $Data_or_field['typeValidate'] == "INDIVIDUAL" ) {
            $keys_to_valdiate = array_keys($Data_or_field)  ;
                foreach ($keys_to_valdiate as $key ) {
                    if (isset($validate_tools[$key])) {
                        $validate_tools_final[$key] =  $validate_tools[$key];
                        $true = true;
                    }
                }
           }

           $validate_tools = (isset($true)) ? $validate_tools_final : $validate_tools;
           $validator = Validator::make($Data_or_field,  $validate_tools );

            if ($validator->fails()) {

                $errors = $validator->errors(); 
                $msg = implode('', $errors->all('<div>:message</div>'));
                return $msg;
            }

            if (isset($Data_or_field['have_user_beneficiary']) && $Data_or_field['have_user_beneficiary'] == 1 && $Data_or_field['email'] == $Data_or_field['user_beneficiary']['email'] ) {
                return "This email ".$Data_or_field['user_beneficiary']['email']." it should not be the same as the main customer";
            }
           
            $value = $Data_or_field['user_address']['zip_code'] ;
            $url = "http://www.test.com/thor/get/$value";
            $context = stream_context_create(array(
            'http' => array(
                'ignore_errors'=>true,
                'method'=>'GET'
                // for more options check http://www.php.net/manual/en/context.http.php
            )
            ));

            $state = json_decode(file_get_contents($url, false, $context));        
            if (!isset($state->data->StateCode)) {
                return 'This ZipCode:'. $value.' is invalid.';
            }else {
                $Data_or_field['user_address']['city'] = $state->data->City ;
                $Data_or_field['user_address']['state'] = $state->data->StateCode ;
            }

           
            if (isset( $Data_or_field['mailing_zip_code'])) {
               $value = $Data_or_field['mailing_zip_code'];
                $url = "http://www.test.com/thor/get/$value";
                $context = stream_context_create(array(
                'http' => array(
                    'ignore_errors'=>true,
                    'method'=>'GET'
                    // for more options check http://www.php.net/manual/en/context.http.php
                )
                ));

                $state = json_decode(file_get_contents($url, false, $context));        
                if (!isset($state->data->StateCode)) {
                    return 'Mailing zipcode '.$value.' is invalid.';
                }else {
                    $Data_or_field['mailing_city'] = $state->data->City ;
                    $Data_or_field['mailing_state'] = $state->data->StateCode ;
                }
            }

            // Set emails_consents
            $Data_or_field['emails_consents'] = (isset($Data_or_field['emails_consents'])) ? $Data_or_field['emails_consents'] : "0" ;
            $Data_or_field['phone_consents'] = (isset($Data_or_field['phone_consents'])) ? $Data_or_field['phone_consents'] : "0" ; 
            $Data_or_field['user_beneficiary']['emails_consents'] = (isset($Data_or_field['user_beneficiary']['emails_consents'])) ? $Data_or_field['user_beneficiary']['emails_consents'] : "0" ;
            $Data_or_field['user_beneficiary']['phone_consents'] = (isset($Data_or_field['user_beneficiary']['phone_consents'])) ? $Data_or_field['user_beneficiary']['phone_consents'] : "0" ;            

            return $Data_or_field;

       }else {
         return json_encode("Data Empty");
       }
    }
}
