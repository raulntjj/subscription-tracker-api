<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Auth;

use Exception;
use RuntimeException;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;
use Modules\Shared\Domain\Contracts\JwtServiceInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;

final class JwtService implements JwtServiceInterface
{
    use Loggable;

    public function __construct(
        private readonly JWTAuth $jwtAuth,
    ) {
    }

    public function attemptLogin(array $credentials): ?string
    {
        $token = auth('api')->attempt($credentials);

        if (!$token) {
            $this->logger()->info('Failed login attempt', [
                'email' => $credentials['email'] ?? 'unknown',
            ]);

            return null;
        }

        $userId = auth('api')->id();
        $this->logger()->audit('User logged in', 'User', $userId, [
            'user_id' => $userId,
            'email'   => $credentials['email'],
        ]);

        return $token;
    }

    public function refreshToken(): string
    {
        try {
            $token = auth('api')->refresh();

            $this->logger()->info('Token refreshed', [
                'user_id' => auth('api')->id(),
            ]);

            return $token;
        } catch (TokenExpiredException $e) {
            $this->logger()->warning('Refresh failed: token expired beyond refresh_ttl', [
                'error' => $e->getMessage(),
            ]);
            
            throw new RuntimeException('Token expirou. Faça login novamente.', 401);
        } catch (Exception $e) {
            $this->logger()->error('Token refresh error', [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    public function invalidateToken(): void
    {
        $userId = auth('api')->id();

        auth('api')->logout();

        $this->logger()->audit('User logged out', 'User', $userId, [
            'user_id' => $userId,
        ]);
    }

    public function getAuthenticatedUser(): mixed
    {
        return auth('api')->user();
    }

    public function getTokenTtl(): int
    {
        return (int) config('jwt.ttl', 60);
    }
}
