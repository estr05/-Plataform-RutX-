<?php

use Illuminate\Support\Facades\Route;

/**
 * Rutas del módulo Venta — scaffold estructural.
 * Día 2 — sin endpoint v2; solo estructura navegable.
 * Cuando exista autenticación real, proteger con middleware 'auth.session'.
 */
Route::prefix('venta')->name('venta.')->group(function () {
    Route::get('/', fn () => view('modules.venta.reportes'))->name('reportes');
    Route::get('/reportes-globales', fn () => view('modules.venta.globales'))->name('globales');
    Route::get('/rentabilidad', fn () => view('modules.venta.rentabilidad'))->name('rentabilidad');
});
