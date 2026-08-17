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
        $response = $this->get('/playground');

        $response->assertStatus(200);
        $response->assertSee('Design System Playground');
        $response->assertSee('KPI Cards');
    }
}
