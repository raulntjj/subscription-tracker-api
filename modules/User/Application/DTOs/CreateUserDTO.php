<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs;

/**
 * DTO para criar um novo usuário
 */
final readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $surname = null,
        public ?string $profilePath = null,
    ) {
    }

    /**
     * Cria DTO a partir de array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            surname: $data['surname'] ?? null,
            profilePath: $data['profile_path'] ?? null,
        );
    }

    /**
     * Converte DTO para array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'surname' => $this->surname,
            'profile_path' => $this->profilePath,
        ];
    }
}
