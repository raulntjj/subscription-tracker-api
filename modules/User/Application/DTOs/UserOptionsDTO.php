<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs;

final readonly class UserOptionsDTO
{
    /**
     * @param array<array{id: string, name: string}> $options
     */
    public function __construct(
        public array $options,
    ) {
    }

    /**
     * Cria um UserOptionsDTO a partir de um array de opções
     *
     * @param array<array{id: string, name: string}> $options
     * @return self
     */
    public static function fromArray(array $options): self
    {
        return new self(options: $options);
    }

    /**
     * Converte o DTO para array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'options' => $this->options,
        ];
    }
}
