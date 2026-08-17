<?php

/**
 * Catálogo de permisos y mapa rol → permisos (RBAC, sprint/2).
 *
 * - 'catalog' es la lista única de permisos válidos; config/navigation.php
 *   solo puede referenciar permisos que existan aquí (validado por test).
 * - 'roles' mapea cada rol a su lista de permisos. 'administrador' usa el
 *   comodín '*' (todos). Las claves técnicas van en inglés; la UI en español.
 * - El middleware 'can:' de Laravel y el Gate registrado en
 *   AppServiceProvider resuelven el permiso vía App\Models\User::hasPermission().
 *
 * La API remota (Hub/Relay) seguirá validando el mismo permiso en segundo
 * plano; ocultar o bloquear la UI no sustituye la autorización del servidor.
 */
return [

    'catalog' => [
        // Módulos
        'module.customers.access',
        'module.products.access',
        'module.inventory.access',
        'module.venta.access',
        'module.ruta.access',
        'module.settings.access',

        // Cliente
        'customers.read',
        'customers.transfer',

        // Producto
        'products.read',
        'products.price.read',

        // Inventario
        'inventory.read',

        // Venta / Reportes
        'reports.read',

        // Ruta
        'routes.monitor',

        // Configuración
        'config.users.read',
        'config.roles.read',
        'config.roles.write',
        'config.zones.read',
    ],

    'roles' => [

        'administrador' => [
            '*',
        ],

        'supervisor' => [
            'module.customers.access',
            'customers.read',
            'customers.transfer',
            'module.products.access',
            'products.read',
            'products.price.read',
            'module.inventory.access',
            'inventory.read',
            'module.venta.access',
            'reports.read',
            'module.ruta.access',
            'routes.monitor',
        ],

        'lector' => [
            'module.customers.access',
            'customers.read',
            'module.products.access',
            'products.read',
            'module.inventory.access',
            'inventory.read',
            'module.venta.access',
            'reports.read',
            'module.ruta.access',
            'routes.monitor',
        ],

    ],

];
