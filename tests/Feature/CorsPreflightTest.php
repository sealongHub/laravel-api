<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsPreflightTest extends TestCase
{
    public function test_frontend_preflight_is_allowed_for_api_routes_without_prefix(): void
    {
        $this->withHeaders([
            'Origin' => 'https://web-pos-ten-silk.vercel.app',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'authorization,content-type',
        ])
            ->options('/product')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'https://web-pos-ten-silk.vercel.app');
    }
}
