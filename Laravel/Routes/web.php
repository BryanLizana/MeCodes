<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your module. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::group(['prefix' => 'admin/broker','as' => 'broker::', 'middleware' => ['web']], function () {
    Route::group(['middleware' => ['test.auth']], function () { //sólo si estás logueado

        Route::get('/', ['as'=>'dashboard','uses'=>'ProductsController@list']);
        Route::get('/dashboard', ['as'=>'dashboard','uses'=>'ProductsController@list']);

        /** Products **/
        Route::get('/product-list', ['as'=>'product-list','uses'=>'ProductsController@list']);
      });
});