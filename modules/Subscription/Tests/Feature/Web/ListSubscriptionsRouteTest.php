<?php

declare(strict_types=1);

namespace Modules\Subscription\Tests\Feature\Web;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Subscription\Tests\Feature\FeatureTestCase;

final class ListSubscriptionsRouteTest extends FeatureTestCase
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

    public function test_can_list_subscriptions_with_pagination(): void
    {
        $this->authenticate();
        $this->createSubscriptions(3, ['user_id' => $this->userId]);

        $response = $this->getJson(
            '/api/web/v1/subscriptions',
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'subscriptions' => [
                        '*' => [
                            'id',
                            'name',
                            'price',
                            'currency',
                            'billing_cycle',
                            'next_billing_date',
                            'category',
                            'status',
                        ],
                    ],
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertGreaterThanOrEqual(3, $response->json('data.total'));
    }

    public function test_can_specify_per_page(): void
    {
        $this->authenticate();
        $this->createSubscriptions(10, ['user_id' => $this->userId]);

        $response = $this->getJson(
            '/api/web/v1/subscriptions?per_page=5',
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200);
        $this->assertEquals(5, $response->json('data.per_page'));
        $this->assertLessThanOrEqual(5, count($response->json('data.subscriptions')));
    }

    public function test_can_navigate_pages(): void
    {
        $this->authenticate();
        $this->createSubscriptions(20, ['user_id' => $this->userId]);

        // Primeira página
        $response1 = $this->getJson(
            '/api/web/v1/subscriptions?page=1&per_page=10',
            $this->authHeaders($this->token),
        );

        // Segunda página
        $response2 = $this->getJson(
            '/api/web/v1/subscriptions?page=2&per_page=10',
            $this->authHeaders($this->token),
        );

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $this->assertEquals(1, $response1->json('data.current_page'));
        $this->assertEquals(2, $response2->json('data.current_page'));
    }

    public function test_can_search_subscriptions_by_name(): void
    {
        $this->authenticate();
        $this->createSubscription([
            'name' => 'Netflix Premium',
            'user_id' => $this->userId,
        ]);
        $this->createSubscription([
            'name' => 'Spotify Music',
            'user_id' => $this->userId,
        ]);

        $response = $this->getJson(
            '/api/web/v1/subscriptions?search=Netflix',
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200);
        $data = $response->json('data.subscriptions');

        $this->assertNotEmpty($data);

        // Verifica se pelo menos um resultado contém "Netflix"
        $hasNetflix = false;
        foreach ($data as $subscription) {
            if (str_contains($subscription['name'], 'Netflix')) {
                $hasNetflix = true;
                break;
            }
        }
        $this->assertTrue($hasNetflix);
    }

    public function test_can_sort_subscriptions_by_name_asc(): void
    {
        $this->authenticate();
        $this->createSubscription([
            'name' => 'Zulu Service',
            'user_id' => $this->userId,
        ]);
        $this->createSubscription([
            'name' => 'Alpha Service',
            'user_id' => $this->userId,
        ]);

        $response = $this->getJson(
            '/api/web/v1/subscriptions?sort_by=name&sort_direction=asc',
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200);
    }

    public function test_can_sort_subscriptions_by_created_at_desc(): void
    {
        $this->authenticate();
        $this->createSubscriptions(3, ['user_id' => $this->userId]);

        $response = $this->getJson(
            '/api/web/v1/subscriptions?sort_by=created_at&sort_direction=desc',
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data.subscriptions'));
    }

    public function test_empty_list_when_no_subscriptions(): void
    {
        $this->authenticate();

        $response = $this->getJson(
            '/api/web/v1/subscriptions',
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/web/v1/subscriptions');
        $this->assertContains($response->status(), [401]);
    }

    public function test_default_pagination_values(): void
    {
        $this->authenticate();

        $response = $this->getJson(
            '/api/web/v1/subscriptions',
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.current_page'));
        $this->assertEquals(15, $response->json('data.per_page'));
    }

    public function test_can_get_subscription_options(): void
    {
        $this->authenticate();
        $this->createSubscriptions(3, ['user_id' => $this->userId]);

        $response = $this->getJson(
            '/api/web/v1/subscriptions/options',
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'options' => [
                        '*' => [
                            'value',
                            'label',
                        ],
                    ],
                ],
            ]);
    }

    public function test_can_search_subscription_options(): void
    {
        $this->authenticate();
        $this->createSubscription([
            'name' => 'Netflix Premium',
            'user_id' => $this->userId,
        ]);

        $response = $this->getJson(
            '/api/web/v1/subscriptions/options?search=Netflix',
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200);
    }
}
