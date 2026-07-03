<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('إبراهيم حسن');
        $response->assertSee('lang="ar"', false);
        $response->assertSee('dir="rtl"', false);
    }
}
