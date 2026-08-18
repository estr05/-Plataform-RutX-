<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NavigationConfigTest extends TestCase
{
    public function test_every_module_default_route_is_registered(): void
    {
        $modules = config('navigation.modules', []);

        $this->assertNotEmpty($modules, 'El catálogo de navegación no debe estar vacío.');

        foreach ($modules as $moduleKey => $module) {
            $this->assertArrayHasKey('default_route', $module, "Módulo {$moduleKey} sin default_route.");

            $defaultRoute = $module['default_route'];
            $this->assertTrue(
                Route::has($defaultRoute),
                "default_route '{$defaultRoute}' del módulo {$moduleKey} no es una ruta registrada."
            );
        }
    }

    public function test_every_navigation_permission_belongs_to_the_catalog(): void
    {
        $catalog = config('permissions.catalog', []);
        $modules = config('navigation.modules', []);

        $this->assertNotEmpty($catalog, 'El catálogo de permisos no debe estar vacío.');

        foreach ($modules as $moduleKey => $module) {
            $this->assertTrue(
                in_array($module['permission'], $catalog, true),
                "Permiso '{$module['permission']}' del módulo {$moduleKey} no está en config/permissions.php."
            );

            foreach ($module['views'] as $viewRoute => $view) {
                $this->assertTrue(
                    in_array($view['permission'], $catalog, true),
                    "Permiso '{$view['permission']}' de la vista {$viewRoute} no está en config/permissions.php."
                );
            }
        }
    }
}
