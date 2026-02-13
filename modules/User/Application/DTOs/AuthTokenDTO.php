<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs;

final readonly class AuthTokenDTO
{
    public function __construct(
        public string $accessToken,
        public string $tokenType,
        public int $expiresIn,
    ) {
    }

    public static function fromToken(string $token, int $ttlMinutes): self
    {
        return new self(
            accessToken: $token,
            tokenType: 'bearer',
            expiresIn: $ttlMinutes * 60,
        );
    }

    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type'   => $this->tokenType,
            'expires_in'   => $this->expiresIn,
        ];
    }
}
