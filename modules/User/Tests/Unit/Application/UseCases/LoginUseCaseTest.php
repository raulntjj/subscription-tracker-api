<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit\Application\UseCases;

use Mockery;
use Modules\User\Tests\UserTestCase;
use Modules\User\Application\DTOs\LoginDTO;
use Modules\User\Application\DTOs\AuthTokenDTO;
use Modules\User\Application\UseCases\LoginUseCase;
use Modules\Shared\Domain\Contracts\JwtServiceInterface;

final class LoginUseCaseTest extends UserTestCase
{
    private JwtServiceInterface $jwtService;
    private LoginUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtService = Mockery::mock(JwtServiceInterface::class);
        $this->useCase = new LoginUseCase($this->jwtService);
    }

    public function test_executes_successful_login(): void
    {
        $email = 'john' . uniqid() . '@example.com';
        $dto = new LoginDTO(
            email: $email,
            password: 'SecurePass123',
        );

        $this->jwtService
            ->shouldReceive('attemptLogin')
            ->once()
            ->with(['email' => $email, 'password' => 'SecurePass123'])
            ->andReturn('fake.jwt.token');

        $this->jwtService
            ->shouldReceive('getTokenTtl')
            ->once()
            ->andReturn(60);

        $result = $this->useCase->execute($dto);

        $this->assertInstanceOf(AuthTokenDTO::class, $result);
        $this->assertEquals('fake.jwt.token', $result->accessToken);
        $this->assertEquals('bearer', $result->tokenType);
        $this->assertEquals(3600, $result->expiresIn);
    }

    public function test_throws_exception_when_credentials_are_invalid(): void
    {
        $email = 'wrong' . uniqid() . '@example.com';
        $dto = new LoginDTO(
            email: $email,
            password: 'WrongPassword',
        );

        $this->jwtService
            ->shouldReceive('attemptLogin')
            ->once()
            ->with(['email' => $email, 'password' => 'WrongPassword'])
            ->andReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Credenciais inválidas.');

        $this->useCase->execute($dto);
    }

    public function test_returns_token_with_custom_ttl(): void
    {
        $dto = new LoginDTO(
            email: 'user@test.com',
            password: 'password123',
        );

        $this->jwtService
            ->shouldReceive('attemptLogin')
            ->once()
            ->andReturn('custom.token');

        $this->jwtService
            ->shouldReceive('getTokenTtl')
            ->once()
            ->andReturn(120); // 2 hours

        $result = $this->useCase->execute($dto);

        $this->assertEquals(7200, $result->expiresIn); // 120 * 60
    }

    public function test_calls_jwt_service_with_correct_credentials(): void
    {
        $email = 'test' . uniqid() . '@example.com';
        $dto = new LoginDTO(
            email: $email,
            password: 'TestPass456',
        );

        $expectedCredentials = [
            'email' => $email,
            'password' => 'TestPass456',
        ];

        $this->jwtService
            ->shouldReceive('attemptLogin')
            ->once()
            ->with($expectedCredentials)
            ->andReturn('token.here');

        $this->jwtService
            ->shouldReceive('getTokenTtl')
            ->once()
            ->andReturn(60);

        $this->useCase->execute($dto);
    }

    public function test_rethrows_unexpected_exceptions(): void
    {
        $dto = new LoginDTO(
            email: 'error@test.com',
            password: 'password',
        );

        $this->jwtService
            ->shouldReceive('attemptLogin')
            ->once()
            ->andThrow(new \RuntimeException('Database connection failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database connection failed');

        $this->useCase->execute($dto);
    }
}
