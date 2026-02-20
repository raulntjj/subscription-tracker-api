<?php

declare(strict_types=1);

namespace Modules\Subscription\Tests\Feature\Web;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Subscription\Tests\Feature\FeatureTestCase;

final class UpdateSubscriptionRouteTest extends FeatureTestCase
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

    public function test_can_update_subscription(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription(['user_id' => $this->userId]);

        $updateData = [
            'name' => 'Netflix Premium Updated',
            'price' => 5990,
            'currency' => 'BRL',
            'billing_cycle' => 'monthly',
            'next_billing_date' => now()->addDays(15)->format('Y-m-d'),
            'category' => 'Streaming Updated',
            'status' => 'active',
        ];

        $response = $this->putJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            $updateData,
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
                    'category',
                    'status',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Netflix Premium Updated', $response->json('data.name'));
        $this->assertEquals(5990, $response->json('data.price'));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'name' => 'Netflix Premium Updated',
            'price' => 5990,
        ]);
    }

    public function test_cannot_update_with_past_billing_date(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription(['user_id' => $this->userId]);

        $updateData = [
            'name' => $subscription->name,
            'price' => $subscription->price,
            'currency' => $subscription->currency,
            'billing_cycle' => $subscription->billing_cycle,
            'next_billing_date' => now()->subDays(10)->format('Y-m-d'),
            'status' => $subscription->status,
        ];

        $response = $this->putJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            $updateData,
            $this->authHeaders($this->token),
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['next_billing_date']);
    }

    public function test_cannot_update_with_negative_price(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription(['user_id' => $this->userId]);

        $updateData = [
            'name' => $subscription->name,
            'price' => -100,
            'currency' => $subscription->currency,
            'billing_cycle' => $subscription->billing_cycle,
            'next_billing_date' => $subscription->next_billing_date,
            'status' => $subscription->status,
        ];

        $response = $this->putJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            $updateData,
            $this->authHeaders($this->token),
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_cannot_update_with_invalid_currency(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription(['user_id' => $this->userId]);

        $updateData = [
            'name' => $subscription->name,
            'price' => $subscription->price,
            'currency' => 'INVALID',
            'billing_cycle' => $subscription->billing_cycle,
            'next_billing_date' => $subscription->next_billing_date,
            'status' => $subscription->status,
        ];

        $response = $this->putJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            $updateData,
            $this->authHeaders($this->token),
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['currency']);
    }

    public function test_cannot_update_with_invalid_billing_cycle(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription(['user_id' => $this->userId]);

        $updateData = [
            'name' => $subscription->name,
            'price' => $subscription->price,
            'currency' => $subscription->currency,
            'billing_cycle' => 'invalid',
            'next_billing_date' => $subscription->next_billing_date,
            'status' => $subscription->status,
        ];

        $response = $this->putJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            $updateData,
            $this->authHeaders($this->token),
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['billing_cycle']);
    }

    public function test_cannot_update_with_invalid_status(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription(['user_id' => $this->userId]);

        $updateData = [
            'name' => $subscription->name,
            'price' => $subscription->price,
            'currency' => $subscription->currency,
            'billing_cycle' => $subscription->billing_cycle,
            'next_billing_date' => $subscription->next_billing_date,
            'status' => 'invalid',
        ];

        $response = $this->putJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            $updateData,
            $this->authHeaders($this->token),
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_returns_404_for_non_existent_subscription(): void
    {
        $this->authenticate();
        $nonExistentId = '550e8400-e29b-41d4-a716-446655440000';

        $updateData = [
            'name' => 'Updated Name',
            'price' => 1000,
            'currency' => 'BRL',
            'billing_cycle' => 'monthly',
            'next_billing_date' => now()->addDays(30)->format('Y-m-d'),
            'status' => 'active',
            'category' => 'health',
        ];

        $response = $this->putJson(
            "/api/web/v1/subscriptions/{$nonExistentId}",
            $updateData,
            $this->authHeaders($this->token),
        );

        $this->assertContains($response->status(), [400, 404]);
    }

    public function test_requires_authentication(): void
    {
        $subscription = $this->createSubscription();

        $response = $this->putJson("/api/web/v1/subscriptions/{$subscription->id}");

        $this->assertContains($response->status(), [401]);
    }

    public function test_can_update_with_status_paused(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription(['user_id' => $this->userId]);

        $updateData = [
            'name' => $subscription->name,
            'price' => $subscription->price,
            'currency' => $subscription->currency,
            'billing_cycle' => $subscription->billing_cycle,
            'next_billing_date' => now()->addDays(30)->format('Y-m-d'),
            'category' => 'New Category',
            'status' => 'paused',
        ];

        $response = $this->putJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            $updateData,
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200);
        $this->assertEquals('paused', $response->json('data.status'));
        $this->assertEquals('New Category', $response->json('data.category'));
    }

    public function test_updated_at_timestamp_changes(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription([
            'user_id' => $this->userId,
            'status' => 'active',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'price' => $subscription->price,
            'currency' => $subscription->currency,
            'billing_cycle' => $subscription->billing_cycle,
            'next_billing_date' => now()->addDays(30)->format('Y-m-d'),
            'category' => $subscription->category,
            'status' => 'active',
        ];

        $response = $this->putJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            $updateData,
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data.updated_at'));
    }
}
