<?php

use Illuminate\Support\Facades\Route;

/**
 * Rutas del módulo Venta.
 * Protegidas por el grupo 'auth' (routes/web.php) y por permiso RBAC vía
 * el middleware 'can:' (config/permissions.php).
 */
Route::prefix('venta')->name('venta.')->middleware('can:module.venta.access')->group(function () {
    Route::get('/', fn () => view('modules.venta.reportes'))->middleware('can:reports.read')->name('reportes');
    Route::get('/reportes-globales', fn () => view('modules.venta.globales'))->middleware('can:reports.read')->name('globales');
    Route::get('/rentabilidad', fn () => view('modules.venta.rentabilidad'))->middleware('can:reports.read')->name('rentabilidad');
});
