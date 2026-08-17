<?php

use Illuminate\Support\Facades\Route;

/**
 * Rutas del módulo Productos.
 * Protegidas por el grupo 'auth' (routes/web.php) y por permiso RBAC vía
 * el middleware 'can:' (config/permissions.php).
 */
Route::prefix('products')->name('products.')->middleware('can:module.products.access')->group(function () {
    Route::get('/', fn () => view('modules.products.index'))->middleware('can:products.read')->name('index');
    Route::get('/prices', fn () => view('modules.products.prices'))->middleware('can:products.price.read')->name('prices');
    Route::get('/zone-prices', fn () => view('modules.products.zone-prices'))->middleware('can:products.price.read')->name('zone-prices');
});
