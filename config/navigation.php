<?php

/**
 * Fuente única de navegación de RutX Web.
 *
 * Este archivo es la única lista de módulos y vistas permitida.
 * - Las claves de módulo y ruta son técnicas (inglés).
 * - Las etiquetas 'label' son UI y van en español de México.
 * - No contiene closures, helpers de request ni URLs resueltas con route():
 *   es seguro para config:cache.
 * - El campo 'permission' es declarativo; los nombres definitivos de claims
 *   se alinearán con Docs/CONTRATOS_WEB_V2.md cuando llegue autenticación.
 *   Ocultar elementos NO reemplaza la autorización de rutas y API.
 *
 * Día 2 — scaffold sin endpoint v2.
 */
return [
    'modules' => [

        'customers' => [
            'label' => 'Cliente',
            'icon' => 'user-group',
            'permission' => 'module.customers.access', // pendiente catálogo de permisos v2
            'default_route' => 'customers.index',
            'views' => [
                'customers.index' => [
                    'label' => 'Clientes',
                    'icon' => 'users',
                    'permission' => 'customers.read',
                ],
                'customers.transfer' => [
                    'label' => 'Traspaso de cliente',
                    'icon' => 'arrows-right-left',
                    'permission' => 'customers.transfer',
                ],
            ],
        ],

        'products' => [
            'label' => 'Producto',
            'icon' => 'cube',
            'permission' => 'module.products.access',
            'default_route' => 'products.index',
            'views' => [
                'products.index' => [
                    'label' => 'Productos',
                    'icon' => 'cube',
                    'permission' => 'products.read',
                ],
                'products.prices' => [
                    'label' => 'Listas de precio',
                    'icon' => 'currency-dollar',
                    'permission' => 'products.price.read',
                ],
                'products.zone-prices' => [
                    'label' => 'Precios por zona',
                    'icon' => 'map-pin',
                    'permission' => 'products.price.read',
                ],
            ],
        ],

        'inventory' => [
            'label' => 'Inventario',
            'icon' => 'archive-box',
            'permission' => 'module.inventory.access',
            'default_route' => 'inventory.routes',
            'views' => [
                'inventory.routes' => [
                    'label' => 'Inventario por ruta',
                    'icon' => 'truck',
                    'permission' => 'inventory.read',
                ],
                'inventory.rejected' => [
                    'label' => 'Productos rechazados',
                    'icon' => 'x-circle',
                    'permission' => 'inventory.read',
                ],
                'inventory.shrinkage' => [
                    'label' => 'Mermas',
                    'icon' => 'trash',
                    'permission' => 'inventory.read',
                ],
            ],
        ],

        'venta' => [
            'label' => 'Venta',
            'icon' => 'shopping-cart',
            'permission' => 'module.venta.access',
            'default_route' => 'venta.reportes', // entrada inicial del chasis
            'views' => [
                'venta.reportes' => [
                    'label' => 'Reportes y Gráficas',
                    'icon' => 'chart-pie',
                    'permission' => 'reports.read',
                ],
                'venta.globales' => [
                    'label' => 'Reportes Globales',
                    'icon' => 'globe-americas',
                    'permission' => 'reports.read',
                ],
                'venta.rentabilidad' => [
                    'label' => 'Rentabilidad',
                    'icon' => 'chart-bar',
                    'permission' => 'reports.read',
                ],
            ],
        ],

        'ruta' => [
            'label' => 'Ruta',
            'icon' => 'truck',
            'permission' => 'module.ruta.access',
            'default_route' => 'ruta.mapa',
            'views' => [
                'ruta.mapa' => [
                    'label' => 'Mapa en tiempo real',
                    'icon' => 'map',
                    'permission' => 'routes.monitor',
                ],
                'ruta.jornada' => [
                    'label' => 'Jornada del día',
                    'icon' => 'sun',
                    'permission' => 'routes.monitor',
                ],
            ],
        ],

        'settings' => [
            'label' => 'Configuración',
            'icon' => 'cog-6-tooth',
            'permission' => 'module.settings.access',
            'default_route' => 'settings.users',
            'views' => [
                'settings.users' => [
                    'label' => 'Usuarios',
                    'icon' => 'users',
                    'permission' => 'config.users.read',
                ],
                'settings.roles' => [
                    'label' => 'Roles',
                    'icon' => 'shield-check',
                    'permission' => 'config.roles.read',
                ],
                'settings.zones' => [
                    'label' => 'Zonas',
                    'icon' => 'map',
                    'permission' => 'config.zones.read',
                ],
            ],
        ],

    ],
];
