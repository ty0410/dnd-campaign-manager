<?php

use Illuminate\Support\Facades\Route;

Route::view('/test-api', 'test-api');
Route::view('/vue-test', 'vue-test');

// Vue Router gestiona las páginas del frontend; Laravel entrega la aplicación
// para las rutas del cliente. Las rutas API continúan definidas en routes/api.php.
Route::view('/{any}', 'vue-test')
    ->where('any', '.*');
