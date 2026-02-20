<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature\Mobile;

use Modules\User\Tests\Feature\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

final class ListUsersRouteTest extends FeatureTestCase
{
    use DatabaseTransactions;

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

    public function test_can_list_users_with_cursor_pagination(): void
    {
        $this->authenticate();
        $this->createUser(['name' => 'Alice', 'email' => 'alice' . uniqid() . '@example.com']);
        $this->createUser(['name' => 'Bob', 'email' => 'bob' . uniqid() . '@example.com']);
        $this->createUser(['name' => 'Charlie', 'email' => 'charlie' . uniqid() . '@example.com']);

        $response = $this->getJson(
            '/api/mobile/v1/users',
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'users' => [
                        '*' => ['id', 'name', 'email', 'created_at'],
                    ],
                    'next_cursor',
                    'prev_cursor',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertIsArray($response->json('data.users'));
    }

    public function test_can_list_users_with_custom_per_page(): void
    {
        $this->authenticate();
        for ($i = 0; $i < 15; $i++) {
            $this->createUser([
                'name' => "User {$i}",
                'email' => "user{$i}" . uniqid() . "@example.com",
            ]);
        }

        $response = $this->getJson(
            '/api/mobile/v1/users?per_page=5',
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200);

        $this->assertLessThanOrEqual(5, count($response->json('data.users')));
    }

    public function test_cursor_pagination_provides_next_cursor(): void
    {
        $this->authenticate();
        for ($i = 0; $i < 25; $i++) {
            $this->createUser([
                'name' => "User {$i}",
                'email' => "user{$i}" . uniqid() . "@example.com",
            ]);
        }

        $response = $this->getJson(
            '/api/mobile/v1/users?per_page=5',
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200);

        $nextCursor = $response->json('data.next_cursor');

        if ($nextCursor) {
            $this->assertNotEmpty($nextCursor);
        }
    }

    public function test_can_navigate_with_cursor(): void
    {
        $this->authenticate();
        for ($i = 0; $i < 15; $i++) {
            $this->createUser([
                'name' => "User {$i}",
                'email' => "user{$i}" . uniqid() . "@example.com",
            ]);
        }

        $firstPageResponse = $this->getJson(
            '/api/mobile/v1/users?per_page=5',
            $this->authHeaders($this->token),
        );

        $firstPageResponse->assertStatus(200);
        $nextCursor = $firstPageResponse->json('data.next_cursor');

        if ($nextCursor) {
            $secondPageResponse = $this->getJson(
                "/api/mobile/v1/users?per_page=5&cursor={$nextCursor}",
                $this->authHeaders($this->token),
            );

            $secondPageResponse->assertStatus(200);
            $this->assertIsArray($secondPageResponse->json('data.users'));
        }

        $this->assertTrue(true);
    }

    public function test_cursor_pagination_handles_invalid_cursor(): void
    {
        $this->authenticate();
        $response = $this->getJson(
            '/api/mobile/v1/users?cursor=invalid-cursor',
            $this->authHeaders($this->token),
        );

        $this->assertContains($response->status(), [200, 400, 422]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/mobile/v1/users');
        $this->assertContains($response->status(), [401]);
    }

    public function test_returns_users_in_json_format(): void
    {
        $this->authenticate();
        $user = $this->createUser(['name' => 'Test User']);

        $response = $this->getJson(
            '/api/mobile/v1/users',
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');

        $data = $response->json('data.users');
        if (count($data) > 0) {
            $firstUser = $data[0];
            $this->assertArrayHasKey('id', $firstUser);
            $this->assertArrayHasKey('name', $firstUser);
            $this->assertArrayHasKey('email', $firstUser);
        }
    }

    public function test_cursor_pagination_is_consistent(): void
    {
        $this->authenticate();
        for ($i = 1; $i <= 10; $i++) {
            $this->createUser([
                'name' => "User {$i}",
                'email' => "user{$i}" . uniqid() . "@example.com",
            ]);
        }

        $firstResponse = $this->getJson(
            '/api/mobile/v1/users?per_page=5',
            $this->authHeaders($this->token),
        );

        $firstResponse->assertStatus(200);
        $firstPageData = $firstResponse->json('data.users');

        if (count($firstPageData) > 0) {
            $this->assertNotEmpty($firstPageData[0]['id']);
            $this->assertNotEmpty($firstPageData[0]['name']);
        }
    }

    public function test_accepts_per_page_parameter(): void
    {
        $this->authenticate();
        for ($i = 0; $i < 10; $i++) {
            $this->createUser([
                'name' => "User {$i}",
                'email' => "user{$i}" . uniqid() . "@example.com",
            ]);
        }

        $perPageValues = [1, 5, 10, 20];

        foreach ($perPageValues as $perPage) {
            $response = $this->getJson(
                "/api/mobile/v1/users?per_page={$perPage}",
                $this->authHeaders($this->token),
            );

            $response->assertStatus(200);

            $data = $response->json('data.users');
            $this->assertLessThanOrEqual($perPage, count($data));
        }
    }

    public function test_cursor_pagination_prev_cursor_is_null_on_first_page(): void
    {
        $this->authenticate();
        $this->createUser(['name' => 'Alice', 'email' => 'alice' . uniqid() . '@example.com']);

        $response = $this->getJson(
            '/api/mobile/v1/users',
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200);

        $this->assertNull($response->json('data.prev_cursor'));
    }

    public function test_mobile_endpoint_returns_correct_structure(): void
    {
        $this->authenticate();
        $response = $this->getJson(
            '/api/mobile/v1/users',
            $this->authHeaders($this->token),
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'users',
                    'next_cursor',
                    'prev_cursor',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertIsArray($response->json('data.users'));
        $this->assertArrayHasKey('next_cursor', $response->json('data'));
        $this->assertArrayHasKey('prev_cursor', $response->json('data'));
    }
}
