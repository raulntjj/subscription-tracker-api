<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature\Web;

use Modules\User\Tests\Feature\FeatureTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class UpdateUserRouteTest extends FeatureTestCase
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

    public function test_can_update_user(): void
    {
        $this->authenticate();
        $user = $this->createUser(['name' => 'Old Name', 'email' => 'old@example.com']);

        $updateData = [
            'name' => 'New Name',
            'email' => 'newemail@example.com',
            'password' => 'NewSecurePass123',
        ];

        $response = $this->putJson(
            "/api/web/v1/users/{$user->id}",
            $updateData,
            $this->authHeaders($this->token)
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'email'],
            ]);

        $this->assertEquals('New Name', $response->json('data.name'));
        $this->assertEquals('newemail@example.com', $response->json('data.email'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'newemail@example.com',
        ]);
    }

    public function test_cannot_update_user_with_invalid_data(): void
    {
        $this->authenticate();
        $user = $this->createUser();

        $updateData = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
        ];

        $response = $this->putJson(
            "/api/web/v1/users/{$user->id}",
            $updateData,
            $this->authHeaders($this->token)
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_requires_authentication(): void
    {
        $user = $this->createUser();
        $response = $this->putJson("/api/web/v1/users/{$user->id}");
        $response->assertStatus(401);
    }
}
