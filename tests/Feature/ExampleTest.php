<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * El chasis (layout + design system) debe renderizar el playground.
     */
    public function test_the_design_system_playground_renders(): void
    {
        // El job PHP del CI no compila assets; sinVite evita que @vite
        // lance 500 cuando no existe el manifest de Vite.
        $this->withoutVite();

        $response = $this->get('/playground');

        $response->assertStatus(200);
        $response->assertSee('Design System Playground');
        $response->assertSee('KPI Cards');
    }
}
