<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs;

use Modules\User\Domain\Entities\User;

/**
 * DTO para representar dados de usuário nas respostas
 */
final readonly class UserDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $createdAt,
        public ?string $updatedAt = null,
        public ?string $surname = null,
        public ?string $profilePath = null,
    ) {}

    /**
     * Cria DTO a partir da entidade de domínio User
     */
    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->id()->toString(),
            name: $user->name(),
            email: $user->email()->value(),
            createdAt: $user->createdAt()->format('Y-m-d H:i:s'),
            surname: $user->surname(),
            profilePath: $user->profilePath(),
        );
    }

    /**
     * Cria DTO a partir de um registro do banco (stdClass ou array)
     */
    public static function fromDatabase(object|array $data): self
    {
        $data = (array) $data;

        return new self(
            id: $data['id'],
            name: $data['name'],
            email: $data['email'],
            createdAt: $data['created_at'],
            updatedAt: $data['updated_at'] ?? null,
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
            'id' => $this->id,
            'name' => $this->name,
            'surname' => $this->surname,
            'email' => $this->email,
            'profile_path' => $this->profilePath,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * Converte DTO para formato de opções
     */
    public function toOptions(): array
    {
        return [
            'value' => $this->id,
            'label' => $this->name . ($this->surname ? ' ' . $this->surname : ''),
        ];
    }
}
