<?php

use Illuminate\Support\Facades\Route;
 //Ruta TEST
    Route::view('/test-api', 'test-api');
    
Route::get('/', function () {
    return view('welcome');
});

Route::view('/vue-test', 'vue-test');
Route::view('/', 'vue-test');
Route::view('/login', 'vue-test');
Route::view('/register', 'vue-test');
Route::view('/campaigns', 'vue-test');