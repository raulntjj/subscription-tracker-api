<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit\Application\UseCases;

use Mockery;
use Illuminate\Foundation\Testing\TestCase;
use Modules\User\Application\DTOs\AuthTokenDTO;
use Modules\Shared\Domain\Contracts\JwtServiceInterface;
use Modules\User\Application\UseCases\RefreshTokenUseCase;

final class RefreshTokenUseCaseTest extends TestCase
{
    private JwtServiceInterface $jwtService;
    private RefreshTokenUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtService = Mockery::mock(JwtServiceInterface::class);
        $this->useCase = new RefreshTokenUseCase($this->jwtService);
    }

    public function test_refreshes_token_successfully(): void
    {
        $this->jwtService
            ->shouldReceive('refreshToken')
            ->once()
            ->andReturn('new.refreshed.token');

        $this->jwtService
            ->shouldReceive('getTokenTtl')
            ->once()
            ->andReturn(60);

        $result = $this->useCase->execute();

        $this->assertInstanceOf(AuthTokenDTO::class, $result);
        $this->assertEquals('new.refreshed.token', $result->accessToken);
        $this->assertEquals('bearer', $result->tokenType);
        $this->assertEquals(3600, $result->expiresIn);
    }

    public function test_returns_token_with_correct_ttl(): void
    {
        $this->jwtService
            ->shouldReceive('refreshToken')
            ->once()
            ->andReturn('token.value');

        $this->jwtService
            ->shouldReceive('getTokenTtl')
            ->once()
            ->andReturn(120); // 2 hours

        $result = $this->useCase->execute();

        $this->assertEquals(7200, $result->expiresIn); // 120 * 60
    }

    public function test_calls_jwt_service_refresh_method(): void
    {
        $this->jwtService
            ->shouldReceive('refreshToken')
            ->once()
            ->andReturn('refreshed.token');

        $this->jwtService
            ->shouldReceive('getTokenTtl')
            ->once()
            ->andReturn(60);

        $this->useCase->execute();
    }

    public function test_handles_refresh_token_exception(): void
    {
        $this->jwtService
            ->shouldReceive('refreshToken')
            ->once()
            ->andThrow(new \RuntimeException('Token refresh failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Token refresh failed');

        $this->useCase->execute();
    }

    public function test_handles_expired_token_exception(): void
    {
        $this->jwtService
            ->shouldReceive('refreshToken')
            ->once()
            ->andThrow(new \InvalidArgumentException('Token has expired and can no longer be refreshed'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Token has expired and can no longer be refreshed');

        $this->useCase->execute();
    }
}
