<?php

declare(strict_types=1);

namespace Modules\Shared\Application\DTOs;

final readonly class JobErrorDTO
{
    public function __construct(
        public string $message,
        public ?string $trace = null,
    ) {
    }

    /**
     * Cria um JobErrorDTO a partir de um array
     * 
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            message: $data['message'],
            trace: $data['trace'] ?? null,
        );
    }

    /**
     * Converte o DTO para array
     * 
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'message' => $this->message,
        ];

        if ($this->trace !== null) {
            $data['trace'] = $this->trace;
        }

        return $data;
    }
}
