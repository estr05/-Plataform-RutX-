<?php

use Illuminate\Support\Facades\Route;

/**
 * Rutas del módulo Clientes.
 * Protegidas por el grupo 'auth' (routes/web.php) y por permiso RBAC vía
 * el middleware 'can:' (config/permissions.php).
 */
Route::prefix('customers')->name('customers.')->middleware('can:module.customers.access')->group(function () {
    Route::get('/', fn () => view('modules.customers.index'))->middleware('can:customers.read')->name('index');
    Route::get('/transfer', fn () => view('modules.customers.transfer'))->middleware('can:customers.transfer')->name('transfer');
});
