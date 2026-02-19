<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Tests\Feature\FeatureTestCase;

final class GetMeRouteTest extends FeatureTestCase
{
    use RefreshDatabase;

    public function test_can_get_authenticated_user(): void
    {
        $auth = $this->authenticateUser([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->getJson(
            '/api/auth/v1/me',
            $this->authHeaders($auth['token'])
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('John Doe', $response->json('data.name'));
        $this->assertEquals('john@example.com', $response->json('data.email'));
    }

    public function test_cannot_get_authenticated_user_without_token(): void
    {
        $response = $this->getJson('/api/auth/v1/me');

        $response->assertStatus(401);
    }

    public function test_cannot_use_invalid_token(): void
    {
        $response = $this->getJson('/api/auth/v1/me', [
            'Authorization' => 'Bearer invalid-token',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }
}
