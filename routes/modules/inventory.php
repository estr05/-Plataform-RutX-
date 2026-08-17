<?php

use Illuminate\Support\Facades\Route;

/**
 * Rutas del módulo Inventario — scaffold estructural.
 * Día 2 — sin endpoint v2; solo estructura navegable.
 * Cuando exista autenticación real, proteger con middleware 'auth.session'.
 */
Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::get('/', fn () => view('modules.inventory.routes'))->name('routes');
    Route::get('/rejected', fn () => view('modules.inventory.rejected'))->name('rejected');
    Route::get('/shrinkage', fn () => view('modules.inventory.shrinkage'))->name('shrinkage');
});
