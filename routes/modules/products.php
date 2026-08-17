<?php

use Illuminate\Support\Facades\Route;

/**
 * Rutas del módulo Producto — scaffold estructural.
 * Día 2 — sin endpoint v2; solo estructura navegable.
 * Cuando exista autenticación real, proteger con middleware 'auth.session'.
 */
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', fn () => view('modules.products.index'))->name('index');
    Route::get('/prices', fn () => view('modules.products.prices'))->name('prices');
    Route::get('/zone-prices', fn () => view('modules.products.zone-prices'))->name('zone-prices');
});
