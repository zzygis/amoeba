<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_reports_database_ok(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJson(['status' => 'ok', 'database' => 'ok']);
    }

    public function test_unknown_api_route_returns_json_404(): void
    {
        // No Accept header on purpose: /api errors must be JSON regardless.
        $this->get('/api/does-not-exist')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }
}
