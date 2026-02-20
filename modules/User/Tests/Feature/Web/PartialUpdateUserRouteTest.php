<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature\Web;

use Modules\User\Tests\Feature\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

final class PartialUpdateUserRouteTest extends FeatureTestCase
{
    use DatabaseTransactions;

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
        $user = $this->createUser(['name' => 'Old Name', 'email' => 'old' . uniqid() . '@example.com']);

        $updateData = [
            'name' => 'Updated Name',
        ];

        $response = $this->patchJson(
            "/api/web/v1/users/{$user->id}",
            $updateData,
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200);
        $this->assertEquals('Updated Name', $response->json('data.name'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => $user->email,
        ]);
    }

    public function test_can_partial_update_only_email(): void
    {
        $this->authenticate();
        $user = $this->createUser(['name' => 'John', 'email' => 'old' . uniqid() . '@example.com']);

        $updateData = [
            'email' => 'newemail' . uniqid() . '@example.com',
        ];

        $response = $this->patchJson(
            "/api/web/v1/users/{$user->id}",
            $updateData,
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200);
        $this->assertEquals($updateData['email'], $response->json('data.email'));
        $this->assertEquals('John', $response->json('data.name'));
    }

    public function test_requires_authentication(): void
    {
        $user = $this->createUser();
        $response = $this->patchJson("/api/web/v1/users/{$user->id}");
        $this->assertContains($response->status(), [401, 429]);
    }
}
