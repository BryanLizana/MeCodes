<?php

namespace App\Modules\Broker\Services;

use App\Modules\Broker\Models\ProductsInventory;
use App\Modules\Broker\Models\Products;
use Mail;

class ProductsInventoryServices
{    

    // Confirmar
    static function confirmarCompra($id_product = null)
    {
        try {
            $product = Products::find($id_product);
            if ($product) {
                $inventory = ProductsInventory::find($product->id_inventory);           
                if ($inventory->quantity > 0) {
                    $inventory->quantity = $inventory->quantity - 1;
                    $inventory->save();
                    if (!empty($inventory->quantity_min_alert) && !empty($inventory->email_min_alert) &&  $inventory->quantity <= $inventory->quantity_min_alert) {               
                        Mail::raw('The stock has reached its limit:'.$product->name, function ($m) use ($inventory) {
                            $m->from('info@com', 'Alert Stock');        
                            $m->to($inventory->email_min_alert)->subject('Alert Stock');
                         });
                    }
                    return array('status' => 'success','message' =>"Success",'data' =>true);
                }else {
                    return array('status' => 'error','message' =>"Error: Quantity is zero",'error' => 1);
                }
            }else {
                return array('status' => 'error','message' =>"Error, id no found",'error' => 1); 
            }
        } catch (Exception $e) {
            //$e->getMessage() ;
            return false;
        } 
        
    }

}