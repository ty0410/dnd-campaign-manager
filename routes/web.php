<?php

use Illuminate\Support\Facades\Route;
 //Ruta TEST
    Route::view('/test-api', 'test-api');
    
Route::get('/', function () {
    return view('welcome');
});
