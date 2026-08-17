<?php

use Illuminate\Support\Facades\Route;

/**
 * Rutas del módulo Clientes — scaffold estructural.
 * Día 2 — sin endpoint v2; solo estructura navegable.
 * Cuando exista autenticación real, proteger con middleware 'auth.session'.
 */
Route::prefix('customers')->name('customers.')->group(function () {
    Route::get('/', fn () => view('modules.customers.index'))->name('index');
    Route::get('/transfer', fn () => view('modules.customers.transfer'))->name('transfer');
});
