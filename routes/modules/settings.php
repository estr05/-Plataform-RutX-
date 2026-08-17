<?php

use Illuminate\Support\Facades\Route;

/**
 * Rutas del módulo Configuración — scaffold estructural.
 * Día 2 — sin endpoint v2; solo estructura navegable.
 * Cuando exista autenticación real, proteger con middleware 'auth.session'.
 */
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', fn () => view('modules.settings.users'))->name('users');
    Route::get('/roles', fn () => view('modules.settings.roles'))->name('roles');
    Route::get('/zones', fn () => view('modules.settings.zones'))->name('zones');
});
