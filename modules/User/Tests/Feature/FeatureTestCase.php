<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature;

use Ramsey\Uuid\Uuid;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class FeatureTestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Creates the application.
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Cria um usuário autenticado e retorna o token
     */
    protected function authenticateUser(array $userData = []): array
    {
        $defaultData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'TestPassword123',
        ];

        $data = array_merge($defaultData, $userData);

        // Cria o usuário no banco
        $user = \Modules\User\Infrastructure\Persistence\Eloquent\UserModel::create([
            'id' => Uuid::uuid4()->toString(),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'created_at' => now(),
        ]);

        // Faz login e obtém o token
        $response = $this->postJson('/api/auth/v1/login', [
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return [
            'user' => $user,
            'token' => $response->json('data.access_token'),
        ];
    }

    /**
     * Retorna headers com autenticação Bearer
     */
    protected function authHeaders(?string $token): array
    {
        return [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ];
    }

    /**
     * Cria um usuário no banco para testes
     */
    protected function createUser(array $data = []): object
    {
        $randomEmail = 'user' . uniqid() . '@example.com';

        $defaultData = [
            'id' => Uuid::uuid4()->toString(),
            'name' => 'John Doe',
            'email' => $randomEmail,
            'password' => bcrypt('SecurePass123'),
            'created_at' => now(),
        ];

        $userData = array_merge($defaultData, $data);

        return \Modules\User\Infrastructure\Persistence\Eloquent\UserModel::create($userData);
    }
}
