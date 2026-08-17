<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Web
|--------------------------------------------------------------------------
| Este archivo actúa como punto de registro, no como catálogo de pantallas.
| Cada módulo declara sus rutas en routes/modules/<modulo>.php.
|
| BARRERA DE DESPLIEGUE:
|   Las rutas scaffold, la redirección raíz (/) y el playground solo se
|   registran fuera del entorno de producción (APP_ENV=production → 404).
|   Cuando llegue la autenticación real, los módulos de negocio se protegen
|   con: Route::middleware(['auth.session'])->group(...).
|--------------------------------------------------------------------------
*/

if (! app()->isProduction()) {
    // Redirección de path fijo para evitar resolver nombres de ruta antes de tiempo
    Route::redirect('/', '/playground')->name('home');

    // Módulos scaffold (estructura navegable)
    require __DIR__.'/modules/customers.php';
    require __DIR__.'/modules/products.php';
    require __DIR__.'/modules/inventory.php';
    require __DIR__.'/modules/venta.php';
    require __DIR__.'/modules/ruta.php';
    require __DIR__.'/modules/settings.php';

    // Playground del design system (solo entorno de desarrollo/testing)
    require __DIR__.'/modules/playground.php';
}
