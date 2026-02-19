<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature\Auth;

use Modules\User\Tests\Feature\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

final class LoginRouteTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_can_login_with_valid_credentials(): void
    {
        $user = $this->createUser([
            'email' => 'user' . uniqid() . '@example.com',
            'password' => bcrypt('SecurePass123'),
        ]);

        $response = $this->postJson('/api/auth/v1/login', [
            'email' => $user->email,
            'password' => 'SecurePass123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('bearer', $response->json('data.token_type'));
        $this->assertNotEmpty($response->json('data.access_token'));
    }

    public function test_cannot_login_with_invalid_email(): void
    {
        $response = $this->postJson('/api/auth/v1/login', [
            'email' => 'invalid-email',
            'password' => 'SecurePass123',
        ]);

        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));
    }

    public function test_cannot_login_with_invalid_password(): void
    {
        $user = $this->createUser([
            'email' => 'user' . uniqid() . '@example.com',
            'password' => bcrypt('CorrectPassword123'),
        ]);

        $response = $this->postJson('/api/auth/v1/login', [
            'email' => $user->email,
            'password' => 'WrongPassword123',
        ]);

        $response->assertStatus(400);
        $this->assertFalse($response->json('success'));
    }

    public function test_login_validation_requires_email(): void
    {
        $response = $this->postJson('/api/auth/v1/login', [
            'password' => 'SecurePass123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_validation_requires_password(): void
    {
        $response = $this->postJson('/api/auth/v1/login', [
            'email' => 'user' . uniqid() . '@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_login_validation_requires_valid_email_format(): void
    {
        $response = $this->postJson('/api/auth/v1/login', [
            'email' => 'invalid-email',
            'password' => 'SecurePass123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_validation_requires_minimum_password_length(): void
    {
        $response = $this->postJson('/api/auth/v1/login', [
            'email' => 'user' . uniqid() . '@example.com',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_login_is_case_sensitive_for_password(): void
    {
        $user = $this->createUser([
            'email' => 'user' . uniqid() . '@example.com',
            'password' => bcrypt('SecurePass123'),
        ]);

        $response = $this->postJson('/api/auth/v1/login', [
            'email' => $user->email,
            'password' => 'SECUREPASS123',
        ]);

        $response->assertStatus(400);
    }

    public function test_login_normalizes_email_to_lowercase(): void
    {
        $user = $this->createUser([
            'email' => 'user' . uniqid() . '@example.com',
            'password' => bcrypt('SecurePass123'),
        ]);

        $response = $this->postJson('/api/auth/v1/login', [
            'email' => strtoupper($user->email),
            'password' => 'SecurePass123',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }
}
