<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Tests\Feature\FeatureTestCase;

final class UserOptionsRouteTest extends FeatureTestCase
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

    public function test_can_get_user_options(): void
    {
        $this->authenticate();
        $this->createUser(['name' => 'Alice', 'email' => 'alice@example.com']);
        $this->createUser(['name' => 'Bob', 'email' => 'bob@example.com']);

        $response = $this->getJson(
            '/api/web/v1/users/options',
            $this->authHeaders($this->token)
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'options' => [
                        '*' => ['id', 'name']
                    ]
                ],
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/web/v1/users/options');
        $response->assertStatus(401);
    }
}
