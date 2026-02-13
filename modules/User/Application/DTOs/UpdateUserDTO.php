<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs;

/**
 * DTO para atualizar dados do usuário
 */
final readonly class UpdateUserDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $password = null,
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
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            password: $data['password'] ?? null,
            surname: $data['surname'] ?? null,
            profilePath: $data['profile_path'] ?? null,
        );
    }

    /**
     * Converte DTO para array apenas com valores preenchidos
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'surname' => $this->surname,
            'profile_path' => $this->profilePath,
        ], fn ($value) => $value !== null);
    }

    /**
     * Verifica se há algum dado para atualizar
     */
    public function hasChanges(): bool
    {
        return $this->name !== null
            || $this->email !== null
            || $this->password !== null
            || $this->surname !== null
            || $this->profilePath !== null;
    }
}
