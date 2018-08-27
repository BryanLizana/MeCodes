<?php

// header('Access-Control-Allow-Origin: *'); 

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your module. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/broker', function (Request $request) {
    // return $request->broker();
})->middleware('auth:api');


Route::group(['middleware' => ['BrokerAuth']], function () {

    Route::get('/test/product/list/filter_by/zipcode/{zipcode}/product/{id_slug_product}', 'ApiBrokerController@listProductCostByZipcode');
   
});

Route::get('/broker/product_cost/list/filter_by/product/{id_or_slug}/', 'ApiBrokerController@listProductCostByZipcodeCopy');


