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

Route::get('/', function () {
    return view('welcome');
});

Route::post('/client', 'SoapController@client');
Route::get('/client', 'SoapController@client');

Route::post('/wallet', 'SoapController@wallet');
Route::get('/wallet', 'SoapController@wallet');

Route::post('/payment', 'SoapController@payment');
Route::get('/payment', 'SoapController@payment');
