<?php

use Illuminate\Support\Facades\Route;

/**
 * Rutas del módulo Ruta.
 * Protegidas por el grupo 'auth' (routes/web.php) y por permiso RBAC vía
 * el middleware 'can:' (config/permissions.php).
 */
Route::prefix('ruta')->name('ruta.')->middleware('can:module.ruta.access')->group(function () {
    Route::get('/', fn () => view('modules.ruta.mapa'))->middleware('can:routes.monitor')->name('mapa');
    Route::get('/jornada', fn () => view('modules.ruta.jornada'))->middleware('can:routes.monitor')->name('jornada');
});
