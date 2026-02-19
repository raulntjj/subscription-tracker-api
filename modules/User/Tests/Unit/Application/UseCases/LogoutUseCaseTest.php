<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit\Application\UseCases;

use Mockery;
use Illuminate\Foundation\Testing\TestCase;
use Modules\User\Application\UseCases\LogoutUseCase;
use Modules\Shared\Domain\Contracts\JwtServiceInterface;

final class LogoutUseCaseTest extends TestCase
{
    private JwtServiceInterface $jwtService;
    private LogoutUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtService = Mockery::mock(JwtServiceInterface::class);
        $this->useCase = new LogoutUseCase($this->jwtService);
    }

    public function test_executes_logout_successfully(): void
    {
        $mockUser = (object) ['id' => '123'];

        $this->jwtService
            ->shouldReceive('getAuthenticatedUser')
            ->once()
            ->andReturn($mockUser);

        $this->jwtService
            ->shouldReceive('invalidateToken')
            ->once();

        $this->useCase->execute();

        // Se chegou aqui sem exceções, o teste passou
        $this->assertTrue(true);
    }

    public function test_executes_logout_when_user_is_null(): void
    {
        $this->jwtService
            ->shouldReceive('getAuthenticatedUser')
            ->once()
            ->andReturn(null);

        $this->jwtService
            ->shouldReceive('invalidateToken')
            ->once();

        $this->useCase->execute();

        $this->assertTrue(true);
    }

    public function test_calls_invalidate_token(): void
    {
        $this->jwtService
            ->shouldReceive('getAuthenticatedUser')
            ->once()
            ->andReturn((object) ['id' => 'user-123']);

        $this->jwtService
            ->shouldReceive('invalidateToken')
            ->once();

        $this->useCase->execute();
    }

    public function test_handles_invalidate_token_exception(): void
    {
        $this->jwtService
            ->shouldReceive('getAuthenticatedUser')
            ->once()
            ->andReturn((object) ['id' => 'test-id']);

        $this->jwtService
            ->shouldReceive('invalidateToken')
            ->once()
            ->andThrow(new \RuntimeException('Token invalidation failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Token invalidation failed');

        $this->useCase->execute();
    }
}
