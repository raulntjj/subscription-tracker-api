<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature\Auth;

use Modules\User\Tests\Feature\FeatureTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class LogoutRouteTest extends FeatureTestCase
{
    use RefreshDatabase;

    public function test_can_logout_with_valid_token(): void
    {
        $auth = $this->authenticateUser();

        $response = $this->postJson(
            '/api/auth/v1/logout',
            [],
            $this->authHeaders($auth['token'])
        );

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    public function test_cannot_logout_without_token(): void
    {
        $response = $this->postJson('/api/auth/v1/logout');

        $response->assertStatus(401);
    }
}
