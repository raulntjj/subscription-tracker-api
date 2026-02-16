<?php

declare(strict_types=1);

namespace Modules\Shared\Console\Commands;

use Illuminate\Console\Command;
use Modules\Shared\Application\Jobs\TestRabbitMQJob;

class TestRabbitMQCommand extends Command
{
    protected $signature = 'test:rabbitmq {message=Teste do RabbitMQ}';

    protected $description = 'Despacha um job de teste para o RabbitMQ';

    public function handle(): int
    {
        $message = $this->argument('message');

        TestRabbitMQJob::dispatch($message);

        $this->info("Job despachado para o RabbitMQ: {$message}");
        $this->info("Verifique os logs em storage/logs/worker.log");

        return self::SUCCESS;
    }
}
