<?php

declare(strict_types=1);

namespace Modules\Subscription\Tests\Feature;

use Ramsey\Uuid\Uuid;
use Modules\Subscription\Tests\SubscriptionTestCase;
use Illuminate\Contracts\Console\Kernel;

abstract class FeatureTestCase extends SubscriptionTestCase
{
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
            'email' => 'test' . uniqid() . '@example.com',
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

        $token = $response->json('data.access_token');

        if ($token === null) {
            $token = \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::fromUser($user);
        }

        return [
            'user' => $user,
            'token' => $token,
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

    /**
     * Cria uma subscription no banco para testes
     */
    protected function createSubscription(array $data = []): object
    {
        $defaultData = [
            'id' => Uuid::uuid4()->toString(),
            'name' => 'Netflix',
            'price' => 4990,
            'currency' => 'BRL',
            'billing_cycle' => 'monthly',
            'next_billing_date' => now()->addDays(10)->format('Y-m-d'),
            'category' => 'Streaming',
            'status' => 'active',
            'user_id' => $data['user_id'] ?? $this->createUser()->id,
            'created_at' => now(),
        ];

        $subscriptionData = array_merge($defaultData, $data);

        return \Modules\Subscription\Infrastructure\Persistence\Eloquent\SubscriptionModel::create($subscriptionData);
    }

    /**
     * Cria múltiplas subscriptions de uma vez
     */
    protected function createSubscriptions(int $count, array $data = []): array
    {
        $subscriptions = [];

        for ($i = 0; $i < $count; $i++) {
            $subscriptions[] = $this->createSubscription($data);
        }

        return $subscriptions;
    }
}
