<?php

use Illuminate\Support\Facades\Route;

/**
 * Rutas del módulo Inventario.
 * Protegidas por el grupo 'auth' (routes/web.php) y por permiso RBAC vía
 * el middleware 'can:' (config/permissions.php).
 */
Route::prefix('inventory')->name('inventory.')->middleware('can:module.inventory.access')->group(function () {
    Route::get('/routes', fn () => view('modules.inventory.routes'))->middleware('can:inventory.read')->name('routes');
    Route::get('/rejected', fn () => view('modules.inventory.rejected'))->middleware('can:inventory.read')->name('rejected');
    Route::get('/shrinkage', fn () => view('modules.inventory.shrinkage'))->middleware('can:inventory.read')->name('shrinkage');
});
