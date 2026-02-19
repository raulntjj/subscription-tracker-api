<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Tests\Feature\FeatureTestCase;

final class PartialUpdateUserRouteTest extends FeatureTestCase
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

    public function test_can_partial_update_user(): void
    {
        $this->authenticate();
        $user = $this->createUser(['name' => 'Old Name', 'email' => 'old@example.com']);

        $updateData = [
            'name' => 'Updated Name',
        ];

        $response = $this->patchJson(
            "/api/web/v1/users/{$user->id}",
            $updateData,
            $this->authHeaders($this->token)
        );

        $response->assertStatus(200);
        $this->assertEquals('Updated Name', $response->json('data.name'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'old@example.com',
        ]);
    }

    public function test_can_partial_update_only_email(): void
    {
        $this->authenticate();
        $user = $this->createUser(['name' => 'John', 'email' => 'old@example.com']);

        $updateData = [
            'email' => 'newemail@example.com',
        ];

        $response = $this->patchJson(
            "/api/web/v1/users/{$user->id}",
            $updateData,
            $this->authHeaders($this->token)
        );

        $response->assertStatus(200);
        $this->assertEquals('newemail@example.com', $response->json('data.email'));
        $this->assertEquals('John', $response->json('data.name'));
    }

    public function test_requires_authentication(): void
    {
        $user = $this->createUser();
        $response = $this->patchJson("/api/web/v1/users/{$user->id}");
        $response->assertStatus(401);
    }
}
