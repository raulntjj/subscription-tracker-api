<?php

declare(strict_types=1);

namespace Modules\Subscription\Tests\Feature\Web;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Subscription\Tests\Feature\FeatureTestCase;

final class DeleteSubscriptionRouteTest extends FeatureTestCase
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

    public function test_can_delete_subscription(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription(['user_id' => $this->userId]);

        $response = $this->deleteJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            [],
            $this->authHeaders($this->token),
        );

        $response->assertStatus(204);

        $this->assertDatabaseMissing('subscriptions', [
            'id' => $subscription->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_delete_active_subscription(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription([
            'user_id' => $this->userId,
            'status' => 'active',
        ]);

        $response = $this->deleteJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            [],
            $this->authHeaders($this->token),
        );

        $response->assertStatus(204);
    }

    public function test_can_delete_paused_subscription(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription([
            'user_id' => $this->userId,
            'status' => 'paused',
        ]);

        $response = $this->deleteJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            [],
            $this->authHeaders($this->token),
        );

        $response->assertStatus(204);
    }

    public function test_can_delete_cancelled_subscription(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription([
            'user_id' => $this->userId,
            'status' => 'cancelled',
        ]);

        $response = $this->deleteJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            [],
            $this->authHeaders($this->token),
        );

        $response->assertStatus(204);
    }

    public function test_can_delete_monthly_subscription(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription([
            'user_id' => $this->userId,
            'billing_cycle' => 'monthly',
        ]);

        $response = $this->deleteJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            [],
            $this->authHeaders($this->token),
        );

        $response->assertStatus(204);
    }

    public function test_can_delete_yearly_subscription(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription([
            'user_id' => $this->userId,
            'billing_cycle' => 'yearly',
        ]);

        $response = $this->deleteJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            [],
            $this->authHeaders($this->token),
        );

        $response->assertStatus(204);
    }

    public function test_returns_404_for_non_existent_subscription(): void
    {
        $this->authenticate();
        $nonExistentId = '00000000-0000-0000-0000-000000000000';

        $response = $this->deleteJson(
            "/api/web/v1/subscriptions/{$nonExistentId}",
            [],
            $this->authHeaders($this->token),
        );

        $response->assertStatus(404);
        $this->assertFalse($response->json('success'));
    }

    public function test_returns_error_for_invalid_uuid(): void
    {
        $this->authenticate();

        $response = $this->deleteJson(
            '/api/web/v1/subscriptions/invalid-uuid',
            [],
            $this->authHeaders($this->token),
        );

        $this->assertContains($response->status(), [404, 500]);
    }

    public function test_requires_authentication(): void
    {
        $subscription = $this->createSubscription();

        $response = $this->deleteJson("/api/web/v1/subscriptions/{$subscription->id}");

        $this->assertContains($response->status(), [401]);
    }

    public function test_subscription_is_actually_removed_from_database(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription(['user_id' => $this->userId]);
        $subscriptionId = $subscription->id;

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscriptionId,
        ]);

        $response = $this->deleteJson(
            "/api/web/v1/subscriptions/{$subscriptionId}",
            [],
            $this->authHeaders($this->token),
        );

        $response->assertStatus(204);

        // Verifica se foi removido ou marcado como deleted
        $deletedSubscription = \Modules\Subscription\Infrastructure\Persistence\Eloquent\SubscriptionModel::withTrashed()
            ->find($subscriptionId);

        $this->assertTrue(
            $deletedSubscription === null || $deletedSubscription->deleted_at !== null,
            'Subscription should be deleted or soft deleted',
        );
    }

    public function test_cannot_show_deleted_subscription(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription(['user_id' => $this->userId]);
        $subscriptionId = $subscription->id;

        // Deleta a subscription
        $this->deleteJson(
            "/api/web/v1/subscriptions/{$subscriptionId}",
            [],
            $this->authHeaders($this->token),
        );

        // Tenta buscar a subscription deletada
        $response = $this->getJson(
            "/api/web/v1/subscriptions/{$subscriptionId}",
            $this->authHeaders($this->token),
        );

        $response->assertStatus(404);
    }

    public function test_delete_response_has_no_content(): void
    {
        $this->authenticate();
        $subscription = $this->createSubscription(['user_id' => $this->userId]);

        $response = $this->deleteJson(
            "/api/web/v1/subscriptions/{$subscription->id}",
            [],
            $this->authHeaders($this->token),
        );

        $response->assertStatus(204);
        $this->assertEmpty($response->getContent());
    }

    public function test_can_delete_subscription_with_different_categories(): void
    {
        $this->authenticate();

        $categories = ['Streaming', 'Software', 'Cloud Storage'];

        foreach ($categories as $category) {
            $subscription = $this->createSubscription([
                'user_id' => $this->userId,
                'category' => $category,
            ]);

            $response = $this->deleteJson(
                "/api/web/v1/subscriptions/{$subscription->id}",
                [],
                $this->authHeaders($this->token),
            );

            $response->assertStatus(204);
        }
    }

    public function test_can_delete_subscription_with_different_currencies(): void
    {
        $this->authenticate();

        $currencies = ['BRL', 'USD', 'EUR'];

        foreach ($currencies as $currency) {
            $subscription = $this->createSubscription([
                'user_id' => $this->userId,
                'currency' => $currency,
            ]);

            $response = $this->deleteJson(
                "/api/web/v1/subscriptions/{$subscription->id}",
                [],
                $this->authHeaders($this->token),
            );

            $response->assertStatus(204);
        }
    }
}
