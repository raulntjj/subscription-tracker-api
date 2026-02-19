<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Tests\Feature\FeatureTestCase;

final class ListUsersRouteTest extends FeatureTestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function authenticate(): void
    {
        $auth = $this->authenticateUser();
        $this->token = $auth['token'];
    }

    public function test_can_list_users_paginated(): void
    {
        $this->authenticate();
        $this->createUser(['name' => 'Alice', 'email' => 'alice@example.com']);
        $this->createUser(['name' => 'Bob', 'email' => 'bob@example.com']);

        $response = $this->getJson(
            '/api/web/v1/users',
            $this->authHeaders($this->token)
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => ['id', 'name', 'email', 'created_at']
                    ],
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertGreaterThanOrEqual(2, $response->json('data.total'));
    }

    public function test_can_list_users_with_pagination_params(): void
    {
        $this->authenticate();
        for ($i = 0; $i < 10; $i++) {
            $this->createUser([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com"
            ]);
        }

        $response = $this->getJson(
            '/api/web/v1/users?page=1&per_page=5',
            $this->authHeaders($this->token)
        );

        $response->assertStatus(200);
        $this->assertEquals(5, $response->json('data.per_page'));
        $this->assertEquals(1, $response->json('data.current_page'));
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/web/v1/users');
        $response->assertStatus(401);
    }
}
