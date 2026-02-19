<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Tests\Feature\FeatureTestCase;

final class RefreshTokenRouteTest extends FeatureTestCase
{
    use RefreshDatabase;

    public function test_can_refresh_token(): void
    {
        $auth = $this->authenticateUser();

        $response = $this->postJson(
            '/api/auth/v1/refresh',
            [],
            $this->authHeaders($auth['token'])
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('data.access_token'));

        $this->assertNotEquals($auth['token'], $response->json('data.access_token'));
    }

    public function test_cannot_refresh_without_token(): void
    {
        $response = $this->postJson('/api/auth/v1/refresh');

        $response->assertStatus(401);
    }
}
