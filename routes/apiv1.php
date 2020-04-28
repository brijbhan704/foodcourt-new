<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//foodcourt@123!
Use App\Rest;
//username--  foodcu
//pss --      foodcourt@123!
//start restaurant
Route::post('register', 'UserController@register');
Route::post('login', 'UserController@authenticate');
Route::post('sendotp', 'UserController@sendotp');

Route::post('restaurantName','RestaurantController@restaurant');
Route::post('getMenulistByID','RestaurantController@getMenuListByID');
Route::post('addtoCart','RestaurantController@AddtoCart');
Route::post('viewCart','RestaurantController@viewCart');
Route::post('updateCart','RestaurantController@updateCart');
Route::post('placeOrder','RestaurantController@placeOrder');
Route::post('deleteUserCart','RestaurantController@deleteUserCart');
Route::post('comment','RestaurantController@comment');
Route::post('fetchComment','RestaurantController@fetchComment');
Route::group(['middleware' => ['jwt.verify']], function() {
    
    
});
