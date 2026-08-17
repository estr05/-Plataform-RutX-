<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Web
|--------------------------------------------------------------------------
| Este archivo actúa como punto de registro, no como catálogo de pantallas.
| Cada módulo declara sus rutas en routes/modules/<modulo>.php.
|
| Autenticación (sprint/2):
|   - /login (GET/POST) es la única ruta pública, con middleware guest.
|   - Todo lo demás vive en el grupo 'auth' (guard web, usuarios en BD).
|   - /logout es POST con CSRF; nunca GET.
|   - El playground del design system solo se registra fuera de producción.
|--------------------------------------------------------------------------
*/

// --- Públicas: solo el flujo de inicio de sesión ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

// --- Autenticadas: home y módulos de negocio en cualquier entorno ---
Route::middleware(['auth'])->group(function () {
    Route::get('/', fn () => view('home'))->name('home');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Módulos de negocio (cada uno declara sus rutas; el grupo 'auth' las protege)
    require __DIR__.'/modules/customers.php';
    require __DIR__.'/modules/products.php';
    require __DIR__.'/modules/inventory.php';
    require __DIR__.'/modules/venta.php';
    require __DIR__.'/modules/ruta.php';
    require __DIR__.'/modules/settings.php';
});

// Playground del design system (solo entorno de desarrollo/testing)
if (! app()->isProduction()) {
    require __DIR__.'/modules/playground.php';
}
