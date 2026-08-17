<?php

use Illuminate\Support\Facades\Route;

/**
 * Rutas del módulo Ruta — scaffold estructural.
 * Día 2 — sin endpoint v2; solo estructura navegable.
 * Cuando exista autenticación real, proteger con middleware 'auth.session'.
 */
Route::prefix('ruta')->name('ruta.')->group(function () {
    Route::get('/', fn () => view('modules.ruta.mapa'))->name('mapa');
    Route::get('/jornada', fn () => view('modules.ruta.jornada'))->name('jornada');
});
