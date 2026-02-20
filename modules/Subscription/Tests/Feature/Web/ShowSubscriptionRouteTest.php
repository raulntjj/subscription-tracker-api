<?php

declare(strict_types=1);

namespace Modules\Subscription\Tests\Feature\Web;

use Modules\Subscription\Tests\Feature\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

final class ShowSubscriptionRouteTest extends FeatureTestCase
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

    public function test_can_show_subscription(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription(['user_id' => $this->userId]);

        $response = $this->getJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200)
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
        $this->assertEquals($subscription->id, $response->json('data.id'));
        $this->assertEquals($subscription->name, $response->json('data.name'));
        $this->assertEquals($subscription->price, $response->json('data.price'));
    }

    public function test_can_show_subscription_with_all_fields(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription([
            'user_id' => $this->userId,
            'name' => 'Netflix Premium',
            'price' => 4990,
            'currency' => 'BRL',
            'billing_cycle' => 'monthly',
            'category' => 'Streaming',
            'status' => 'active',
        ]);

        $response = $this->getJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertEquals('Netflix Premium', $data['name']);
        $this->assertEquals(4990, $data['price']);
        $this->assertEquals('BRL', $data['currency']);
        $this->assertEquals('monthly', $data['billing_cycle']);
        $this->assertEquals('Streaming', $data['category']);
        $this->assertEquals('active', $data['status']);
    }

    public function test_returns_404_for_non_existent_subscription(): void
    {
        $this->authenticate();
        $nonExistentId = '00000000-0000-0000-0000-000000000000';

        $response = $this->getJson(
            "/api/web/v1/subscriptions/{$nonExistentId}",
            $this->authHeaders($this->token),
        );

        $response->assertStatus(404);
        $this->assertFalse($response->json('success'));
    }

    public function test_returns_404_for_invalid_uuid(): void
    {
        $this->authenticate();

        $response = $this->getJson(
            '/api/web/v1/subscriptions/invalid-uuid',
            $this->authHeaders($this->token),
        );

        // UUID inválido
        $this->assertContains($response->status(), [400]);

        $response = $this->getJson(
            '/api/web/v1/subscriptions/00000000-0000-0000-0000-000000000000',
            $this->authHeaders($this->token),
        );

        // UUID não existente
        $this->assertContains($response->status(), [404]);
    }

    public function test_requires_authentication(): void
    {
        $subscription = $this->createSubscription();

        $response = $this->getJson("/api/web/v1/subscriptions/{$subscription->id}");
        
        $this->assertContains($response->status(), [401, 429]);
    }

    public function test_can_show_subscription_with_different_currencies(): void
    {
        $this->authenticate();

        $currencies = ['BRL', 'USD', 'EUR'];

        foreach ($currencies as $currency) {
            $subscription = $this->createSubscription([
                'user_id' => $this->userId,
                'currency' => $currency,
            ]);

            $response = $this->getJson(
                "/api/web/v1/subscriptions/{$subscription->id}",
                $this->authHeaders($this->token),
            );

            $response->assertStatus(200);
            $this->assertEquals($currency, $response->json('data.currency'));
        }
    }

    public function test_can_show_subscription_with_different_billing_cycles(): void
    {
        $this->authenticate();

        $cycles = ['monthly', 'yearly'];

        foreach ($cycles as $cycle) {
            $subscription = $this->createSubscription([
                'user_id' => $this->userId,
                'billing_cycle' => $cycle,
            ]);

            $response = $this->getJson(
                "/api/web/v1/subscriptions/{$subscription->id}",
                $this->authHeaders($this->token),
            );

            $response->assertStatus(200);
            $this->assertEquals($cycle, $response->json('data.billing_cycle'));
        }
    }

    public function test_can_show_subscription_with_different_statuses(): void
    {
        $this->authenticate();

        $statuses = ['active', 'paused', 'cancelled'];

        foreach ($statuses as $status) {
            $subscription = $this->createSubscription([
                'user_id' => $this->userId,
                'status' => $status,
            ]);

            $response = $this->getJson(
                "/api/web/v1/subscriptions/{$subscription->id}",
                $this->authHeaders($this->token),
            );

            $response->assertStatus(200);
            $this->assertEquals($status, $response->json('data.status'));
        }
    }

    public function test_response_includes_formatted_price(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription([
            'user_id' => $this->userId,
            'price' => 4990, // R$ 49,90
        ]);

        $response = $this->getJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200);
        $this->assertArrayHasKey('price_formatted', $response->json('data'));
    }

    public function test_response_includes_timestamps(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription(['user_id' => $this->userId]);

        $response = $this->getJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data.created_at'));
    }
}
