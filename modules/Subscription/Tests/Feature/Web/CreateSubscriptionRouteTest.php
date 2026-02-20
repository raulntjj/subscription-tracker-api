<?php

declare(strict_types=1);

namespace Modules\Subscription\Tests\Feature\Web;

use Modules\Subscription\Tests\Feature\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

final class CreateSubscriptionRouteTest extends FeatureTestCase
{
    use DatabaseTransactions;

    private string $token;
    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function authenticate(): void
    {
        $auth = $this->authenticateUser();
        $this->token = $auth['token'];
        $this->userId = $auth['user']->id;
    }

    public function test_can_create_subscription(): void
    {
        $this->authenticate();
        $subscriptionData = [
            'name' => 'Netflix Premium',
            'price' => 4990,
            'currency' => 'BRL',
            'billing_cycle' => 'monthly',
            'next_billing_date' => now()->addDays(10)->format('Y-m-d'),
            'category' => 'Streaming',
            'status' => 'active',
        ];

        $response = $this->postJson(
            '/api/web/v1/subscriptions',
            $subscriptionData,
            $this->authHeaders($this->token),
        );

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'price',
                    'currency',
                    'billing_cycle',
                    'next_billing_date',
                    'category',
                    'status',
                    'user_id',
                    'created_at',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Netflix Premium', $response->json('data.name'));
        $this->assertEquals(4990, $response->json('data.price'));
        $this->assertEquals('BRL', $response->json('data.currency'));
        $this->assertEquals('monthly', $response->json('data.billing_cycle'));

        $this->assertDatabaseHas('subscriptions', [
            'name' => 'Netflix Premium',
            'price' => 4990,
            'user_id' => $this->userId,
        ]);
    }

    public function test_can_create_subscription_with_yearly_cycle(): void
    {
        $this->authenticate();
        $subscriptionData = [
            'name' => 'Adobe Creative Cloud',
            'price' => 59880,
            'currency' => 'BRL',
            'billing_cycle' => 'yearly',
            'next_billing_date' => now()->addYear()->format('Y-m-d'),
            'category' => 'Software',
            'status' => 'active',
        ];

        $response = $this->postJson(
            '/api/web/v1/subscriptions',
            $subscriptionData,
            $this->authHeaders($this->token),
        );

        $response->assertStatus(201);
        $this->assertEquals('yearly', $response->json('data.billing_cycle'));
    }

    public function test_can_create_subscription_with_different_currencies(): void
    {
        $this->authenticate();

        $currencies = ['BRL', 'USD', 'EUR'];

        foreach ($currencies as $currency) {
            $subscriptionData = [
                'name' => 'Test Subscription ' . $currency,
                'price' => 1000,
                'currency' => $currency,
                'billing_cycle' => 'monthly',
                'next_billing_date' => now()->addDays(10)->format('Y-m-d'),
                'category' => 'Test',
                'status' => 'active',
            ];

            $response = $this->postJson(
                '/api/web/v1/subscriptions',
                $subscriptionData,
                $this->authHeaders($this->token),
            );

            $response->assertStatus(201);
            $this->assertEquals($currency, $response->json('data.currency'));
        }
    }

    public function test_cannot_create_subscription_without_required_fields(): void
    {
        $this->authenticate();

        $response = $this->postJson(
            '/api/web/v1/subscriptions',
            [],
            $this->authHeaders($this->token),
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'price',
                'currency',
                'billing_cycle',
                'next_billing_date',
                'category',
            ]);
    }

    public function test_cannot_create_subscription_with_invalid_currency(): void
    {
        $this->authenticate();
        $subscriptionData = [
            'name' => 'Test Subscription',
            'price' => 1000,
            'currency' => 'INVALID',
            'billing_cycle' => 'monthly',
            'next_billing_date' => now()->addDays(10)->format('Y-m-d'),
            'category' => 'Test',
            'status' => 'active',
        ];

        $response = $this->postJson(
            '/api/web/v1/subscriptions',
            $subscriptionData,
            $this->authHeaders($this->token),
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['currency']);
    }

    public function test_cannot_create_subscription_with_invalid_billing_cycle(): void
    {
        $this->authenticate();
        $subscriptionData = [
            'name' => 'Test Subscription',
            'price' => 1000,
            'currency' => 'BRL',
            'billing_cycle' => 'invalid',
            'next_billing_date' => now()->addDays(10)->format('Y-m-d'),
            'category' => 'Test',
            'status' => 'active',
        ];

        $response = $this->postJson(
            '/api/web/v1/subscriptions',
            $subscriptionData,
            $this->authHeaders($this->token),
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['billing_cycle']);
    }

    public function test_cannot_create_subscription_with_negative_price(): void
    {
        $this->authenticate();
        $subscriptionData = [
            'name' => 'Test Subscription',
            'price' => -100,
            'currency' => 'BRL',
            'billing_cycle' => 'monthly',
            'next_billing_date' => now()->addDays(10)->format('Y-m-d'),
            'category' => 'Test',
            'status' => 'active',
        ];

        $response = $this->postJson(
            '/api/web/v1/subscriptions',
            $subscriptionData,
            $this->authHeaders($this->token),
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_cannot_create_subscription_with_past_billing_date(): void
    {
        $this->authenticate();
        $subscriptionData = [
            'name' => 'Test Subscription',
            'price' => 1000,
            'currency' => 'BRL',
            'billing_cycle' => 'monthly',
            'next_billing_date' => now()->subDays(10)->format('Y-m-d'),
            'category' => 'Test',
            'status' => 'active',
        ];

        $response = $this->postJson(
            '/api/web/v1/subscriptions',
            $subscriptionData,
            $this->authHeaders($this->token),
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['next_billing_date']);
    }

    public function test_cannot_create_subscription_with_invalid_status(): void
    {
        $this->authenticate();
        $subscriptionData = [
            'name' => 'Test Subscription',
            'price' => 1000,
            'currency' => 'BRL',
            'billing_cycle' => 'monthly',
            'next_billing_date' => now()->addDays(10)->format('Y-m-d'),
            'category' => 'Test',
            'status' => 'invalid',
        ];

        $response = $this->postJson(
            '/api/web/v1/subscriptions',
            $subscriptionData,
            $this->authHeaders($this->token),
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_can_create_subscription_with_zero_price(): void
    {
        $this->authenticate();
        $subscriptionData = [
            'name' => 'Free Trial',
            'price' => 0,
            'currency' => 'BRL',
            'billing_cycle' => 'monthly',
            'next_billing_date' => now()->addDays(10)->format('Y-m-d'),
            'category' => 'Trial',
            'status' => 'active',
        ];

        $response = $this->postJson(
            '/api/web/v1/subscriptions',
            $subscriptionData,
            $this->authHeaders($this->token),
        );

        $response->assertStatus(201);
        $this->assertEquals(0, $response->json('data.price'));
    }

    public function test_requires_authentication(): void
    {
        $response = $this->postJson('/api/web/v1/subscriptions');
        $this->assertContains($response->status(), [401, 429]);
    }

    public function test_user_id_is_set_from_authenticated_user(): void
    {
        $this->authenticate();
        $subscriptionData = [
            'name' => 'Test Subscription',
            'price' => 1000,
            'currency' => 'BRL',
            'billing_cycle' => 'monthly',
            'next_billing_date' => now()->addDays(10)->format('Y-m-d'),
            'category' => 'Test',
            'status' => 'active',
        ];

        $response = $this->postJson(
            '/api/web/v1/subscriptions',
            $subscriptionData,
            $this->authHeaders($this->token),
        );

        $response->assertStatus(201);
        $this->assertEquals($this->userId, $response->json('data.user_id'));
    }
}
