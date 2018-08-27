<?php 

// isEmpty is when use '->get();'
 function listProducts()
{      
    $Products    = Products::select('broker_products.id')->get();                                    
    if ($Products->isEmpty()) {

    }else {
        if (true ) {
            // CONVERT TO ARRAY an Object Model
            $product_detail['category']       = Categories::find($product_detail['id_category'])->toArray();
            $list_items[] = $product_detail;
        }
    }
}

// is true/false when use 'find()' or '->first();'
$saleEdit = Product::find(1);
if (!$saleEdit) {
    // Error, is empty
}
