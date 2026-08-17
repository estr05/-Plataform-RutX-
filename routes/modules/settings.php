<?php

use Illuminate\Support\Facades\Route;

/**
 * Rutas del módulo Configuración.
 * Protegidas por el grupo 'auth' (routes/web.php) y por permiso RBAC vía
 * el middleware 'can:' (config/permissions.php). Solo el rol administrador
 * accede a Configuración (config/permissions.php).
 */
Route::prefix('settings')->name('settings.')->middleware('can:module.settings.access')->group(function () {
    Route::get('/', fn () => view('modules.settings.users'))->middleware('can:config.users.read')->name('users');
    Route::get('/roles', fn () => view('modules.settings.roles'))->middleware('can:config.roles.read')->name('roles');
    Route::get('/zones', fn () => view('modules.settings.zones'))->middleware('can:config.zones.read')->name('zones');
});
