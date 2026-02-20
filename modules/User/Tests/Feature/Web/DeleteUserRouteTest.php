<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature\Web;

use Modules\User\Tests\Feature\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

final class DeleteUserRouteTest extends FeatureTestCase
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

    public function test_can_delete_user(): void
    {
        $this->authenticate();
        $user = $this->createUser(['email' => 'todelete' . uniqid() . '@example.com']);

        $response = $this->deleteJson(
            "/api/web/v1/users/{$user->id}",
            [],
            $this->authHeaders($this->token),
        );

        $response->assertStatus(204);
        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }

    public function test_cannot_delete_nonexistent_user(): void
    {
        $this->authenticate();
        $fakeId = '550e8400-e29b-41d4-a716-446655440099';

        $response = $this->deleteJson(
            "/api/web/v1/users/{$fakeId}",
            [],
            $this->authHeaders($this->token),
        );

        $response->assertStatus(404);
    }

    public function test_requires_authentication(): void
    {
        $user = $this->createUser(['email' => 'testuser' . uniqid() . '@example.com']);
        $response = $this->deleteJson("/api/web/v1/users/{$user->id}");
        $this->assertContains($response->status(), [401]);
    }
}
