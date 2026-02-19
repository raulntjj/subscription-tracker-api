<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature\Web;

use Modules\User\Tests\Feature\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

final class ShowUserRouteTest extends FeatureTestCase
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

    public function test_can_show_user_by_id(): void
    {
        $this->authenticate();
        $user = $this->createUser(['name' => 'Alice', 'email' => 'alice' . uniqid() . '@example.com']);

        $response = $this->getJson(
            "/api/web/v1/users/{$user->id}",
            $this->authHeaders($this->token)
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'email'],
            ]);

        $this->assertEquals($user->name, $response->json('data.name'));
        $this->assertEquals($user->email, $response->json('data.email'));
    }

    public function test_returns_404_when_user_not_found(): void
    {
        $this->authenticate();
        $fakeId = '550e8400-e29b-41d4-a716-446655440099';

        $response = $this->getJson(
            "/api/web/v1/users/{$fakeId}",
            $this->authHeaders($this->token)
        );

        $response->assertStatus(404);
    }

    public function test_requires_authentication(): void
    {
        $user = $this->createUser();
        $response = $this->getJson("/api/web/v1/users/{$user->id}");
        $this->assertContains($response->status(), [401, 429]);
    }
}
