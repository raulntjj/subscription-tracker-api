<?php

declare(strict_types=1);

namespace Modules\User\Tests\Feature\Auth;

use Modules\User\Tests\Feature\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

final class RegisterRouteTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_can_register_with_valid_data(): void
    {
        $email = 'john.doe' . uniqid() . '@example.com';
        $response = $this->postJson('/api/auth/v1/register', [
            'name' => 'John',
            'surname' => 'Doe',
            'email' => $email,
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertStatus(201)
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

        // Verifica se o usuário foi criado no banco
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'name' => 'John',
            'surname' => 'Doe',
        ]);
    }

    public function test_can_register_without_optional_fields(): void
    {
        $email = 'jane.doe' . uniqid() . '@example.com';
        $response = $this->postJson('/api/auth/v1/register', [
            'name' => 'Jane',
            'email' => $email,
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertStatus(201);
        $this->assertTrue($response->json('success'));

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'name' => 'Jane',
        ]);
    }

    public function test_cannot_register_with_existing_email(): void
    {
        $email = 'existing' . uniqid() . '@example.com';
        // Cria usuário existente
        $this->createUser([
            'email' => $email,
        ]);

        $response = $this->postJson('/api/auth/v1/register', [
            'name' => 'John',
            'email' => $email,
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertStatus(400);
        $this->assertFalse($response->json('success'));
        $this->assertStringContainsString('já está em uso', $response->json('message'));
    }

    public function test_register_validation_requires_name(): void
    {
        $response = $this->postJson('/api/auth/v1/register', [
            'email' => 'test' . uniqid() . '@example.com',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_register_validation_requires_email(): void
    {
        $response = $this->postJson('/api/auth/v1/register', [
            'name' => 'John',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_validation_requires_valid_email_format(): void
    {
        $response = $this->postJson('/api/auth/v1/register', [
            'name' => 'John',
            'email' => 'invalid-email',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_validation_requires_password(): void
    {
        $response = $this->postJson('/api/auth/v1/register', [
            'name' => 'John',
            'email' => 'test' . uniqid() . '@example.com',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_validation_requires_minimum_password_length(): void
    {
        $response = $this->postJson('/api/auth/v1/register', [
            'name' => 'John',
            'email' => 'test' . uniqid() . '@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_validation_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/auth/v1/register', [
            'name' => 'John',
            'email' => 'test' . uniqid() . '@example.com',
            'password' => 'SecurePass123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_validation_requires_matching_password_confirmation(): void
    {
        $response = $this->postJson('/api/auth/v1/register', [
            'name' => 'John',
            'email' => 'test' . uniqid() . '@example.com',
            'password' => 'SecurePass123',
            'password_confirmation' => 'DifferentPass123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_normalizes_email_to_lowercase(): void
    {
        $email = 'JOHN.DOE' . uniqid() . '@EXAMPLE.COM';
        $response = $this->postJson('/api/auth/v1/register', [
            'name' => 'John',
            'email' => strtoupper($email),
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertStatus(201);
        $this->assertTrue($response->json('success'));

        // Verifica se o email foi normalizado para lowercase
        $this->assertDatabaseHas('users', [
            'email' => strtolower($email),
        ]);
    }

    public function test_registered_user_can_login_immediately(): void
    {
        $email = 'john' . uniqid() . '@example.com';
        // Registra usuário
        $registerResponse = $this->postJson('/api/auth/v1/register', [
            'name' => 'John',
            'email' => $email,
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $registerResponse->assertStatus(201);

        // Tenta fazer login com as mesmas credenciais
        $loginResponse = $this->postJson('/api/auth/v1/login', [
            'email' => $email,
            'password' => 'SecurePass123',
        ]);

        $loginResponse->assertStatus(200);
        $this->assertTrue($loginResponse->json('success'));
        $this->assertNotEmpty($loginResponse->json('data.access_token'));
    }

    public function test_register_returns_valid_jwt_token(): void
    {
        $email = 'john' . uniqid() . '@example.com';
        $response = $this->postJson('/api/auth/v1/register', [
            'name' => 'John',
            'email' => $email,
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertStatus(201);
        $token = $response->json('data.access_token');

        // Testa se o token funciona em uma rota autenticada
        $meResponse = $this->getJson('/api/auth/v1/me', [
            'Authorization' => "Bearer {$token}",
        ]);

        $meResponse->assertStatus(200);
        $this->assertEquals($email, $meResponse->json('data.email'));
    }

    public function test_register_with_surname_stores_correctly(): void
    {
        $email = 'john.smith' . uniqid() . '@example.com';
        $response = $this->postJson('/api/auth/v1/register', [
            'name' => 'John',
            'surname' => 'Smith',
            'email' => $email,
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'name' => 'John',
            'surname' => 'Smith',
        ]);
    }

    public function test_register_validation_rejects_name_exceeding_max_length(): void
    {
        $email = 'test' . uniqid() . '@example.com';
        $response = $this->postJson('/api/auth/v1/register', [
            'name' => str_repeat('a', 256), // 256 characters
            'email' => $email,
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_register_validation_rejects_email_exceeding_max_length(): void
    {
        $longEmail = str_repeat('a', 245) . '@example.com'; // > 255 characters

        $response = $this->postJson('/api/auth/v1/register', [
            'name' => 'John',
            'email' => $longEmail,
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
