<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature\Auth;

use Modules\User\Tests\Feature\FeatureTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class CompleteAuthFlowTest extends FeatureTestCase
{
    use RefreshDatabase;

    public function test_complete_authentication_flow(): void
    {
        // 1. Cria um usuário
        $user = $this->createUser([
            'name' => 'Flow Test',
            'email' => 'flow@example.com',
            'password' => bcrypt('FlowPass123'),
        ]);

        // 2. Faz login
        $loginResponse = $this->postJson('/api/auth/v1/login', [
            'email' => 'flow@example.com',
            'password' => 'FlowPass123',
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.access_token');

        // 3. Verifica dados do usuário autenticado
        $meResponse = $this->getJson(
            '/api/auth/v1/me',
            $this->authHeaders($token)
        );

        $meResponse->assertStatus(200);
        $this->assertEquals('Flow Test', $meResponse->json('data.name'));

        // 4. Renova o token
        $refreshResponse = $this->postJson(
            '/api/auth/v1/refresh',
            [],
            $this->authHeaders($token)
        );

        $refreshResponse->assertStatus(200);
        $newToken = $refreshResponse->json('data.access_token');
        $this->assertNotEquals($token, $newToken);

        // 5. Usa o novo token
        $meResponse2 = $this->getJson(
            '/api/auth/v1/me',
            $this->authHeaders($newToken)
        );

        $meResponse2->assertStatus(200);
        $this->assertEquals('Flow Test', $meResponse2->json('data.name'));

        // 6. Faz logout
        $logoutResponse = $this->postJson(
            '/api/auth/v1/logout',
            [],
            $this->authHeaders($newToken)
        );

        $logoutResponse->assertStatus(200);
    }
}
