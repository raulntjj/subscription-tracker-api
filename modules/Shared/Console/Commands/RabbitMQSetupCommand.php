<?php

declare(strict_types=1);

namespace Modules\Shared\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;

final class RabbitMQSetupCommand extends Command
{
    protected $signature = 'rabbitmq:setup 
                            {--force : Força a recriação das filas existentes}';

    protected $description = 'Configura as filas necessárias no RabbitMQ';

    private array $queues = [
        'default' => [
            'durable' => true,
            'exclusive' => false,
            'auto_delete' => false,
        ],
        'billing' => [
            'durable' => true,
            'exclusive' => false,
            'auto_delete' => false,
        ],
        'webhooks' => [
            'durable' => true,
            'exclusive' => false,
            'auto_delete' => false,
        ],
    ];

    private array $exchanges = [
        'laravel' => [
            'type' => 'direct',
            'durable' => true,
            'auto_delete' => false,
        ],
    ];

    public function handle(): int
    {
        $this->info('Configurando RabbitMQ...');
        $this->newLine();

        try {
            $connection = new AMQPStreamConnection(
                config('queue.connections.rabbitmq.host'),
                config('queue.connections.rabbitmq.port'),
                config('queue.connections.rabbitmq.login'),
                config('queue.connections.rabbitmq.password'),
                config('queue.connections.rabbitmq.vhost')
            );

            $channel = $connection->channel();

            // Criar exchanges
            $this->createExchanges($channel);
            $this->newLine();

            // Criar filas
            $this->createQueues($channel);
            $this->newLine();

            // Bind filas aos exchanges
            $this->bindQueues($channel);

            $channel->close();
            $connection->close();

            $this->newLine();
            $this->info('RabbitMQ configurado com sucesso!');
            $this->newLine();
            $this->comment('Agora você pode iniciar os workers:');
            $this->line('php artisan queue:work rabbitmq --queue=default');
            $this->line('php artisan queue:work rabbitmq --queue=billing');
            $this->line('php artisan queue:work rabbitmq --queue=webhooks');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Erro ao configurar RabbitMQ:');
            $this->error($e->getMessage());

            if ($this->output->isVerbose()) {
                $this->newLine();
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    private function createExchanges($channel): void
    {
        $this->info('Criando exchanges...');

        foreach ($this->exchanges as $name => $config) {
            try {
                $channel->exchange_declare(
                    $name,
                    $config['type'],
                    false, // passive
                    $config['durable'],
                    $config['auto_delete']
                );

                $this->line("Exchange '{$name}' criado");
            } catch (\Throwable $e) {
                $this->warn("Exchange '{$name}': {$e->getMessage()}");
            }
        }
    }

    private function createQueues($channel): void
    {
        $this->info('Criando filas...');

        foreach ($this->queues as $name => $config) {
            try {
                $channel->queue_declare(
                    $name,
                    false, // passive
                    $config['durable'],
                    $config['exclusive'],
                    $config['auto_delete']
                );

                $this->line("Fila '{$name}' criada");
            } catch (\Throwable $e) {
                $this->warn("Fila '{$name}': {$e->getMessage()}");
            }
        }
    }

    private function bindQueues($channel): void
    {
        $this->info('Fazendo bind das filas aos exchanges...');

        $exchangeName = config('queue.connections.rabbitmq.options.exchange.name', 'laravel');

        foreach (array_keys($this->queues) as $queueName) {
            try {
                $channel->queue_bind($queueName, $exchangeName, $queueName);
                $this->line("Fila '{$queueName}' vinculada ao exchange '{$exchangeName}'");
            } catch (\Throwable $e) {
                $this->warn("Bind '{$queueName}': {$e->getMessage()}");
            }
        }
    }
}
