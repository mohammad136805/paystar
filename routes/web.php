<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//dd(Route::current());
Route::get('/check-out',"App\Http\Controllers\CheckOutController@checkOut")->name('checkOutUrl');
Route::match(['post' , 'get'],'/verify-payment',"App\Http\Controllers\CheckOutController@verifyPayment")->name('verifyUrl');
Route::match(['post' , 'get'],'/call-back',"App\Http\Controllers\CheckOutController@callBack")->name('callBackUrl');



