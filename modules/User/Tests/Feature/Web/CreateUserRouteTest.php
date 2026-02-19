<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Tests\Feature\FeatureTestCase;

final class CreateUserRouteTest extends FeatureTestCase
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

    public function test_can_create_user(): void
    {
        $this->authenticate();
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'SecurePass123',
            'surname' => 'Test',
        ];

        $response = $this->postJson(
            '/api/web/v1/users',
            $userData,
            $this->authHeaders($this->token)
        );

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'email'],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('New User', $response->json('data.name'));
        $this->assertEquals('newuser@example.com', $response->json('data.email'));

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
        ]);
    }

    public function test_cannot_create_user_with_duplicate_email(): void
    {
        $this->authenticate();
        $this->createUser(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'Another User',
            'email' => 'existing@example.com',
            'password' => 'SecurePass123',
        ];

        $response = $this->postJson(
            '/api/web/v1/users',
            $userData,
            $this->authHeaders($this->token)
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_cannot_create_user_with_invalid_data(): void
    {
        $this->authenticate();
        $userData = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
        ];

        $response = $this->postJson(
            '/api/web/v1/users',
            $userData,
            $this->authHeaders($this->token)
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->postJson('/api/web/v1/users');
        $response->assertStatus(401);
    }
}
