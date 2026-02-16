<?php

declare(strict_types=1);

namespace Modules\Shared\Console\Commands;

use Illuminate\Console\Command;
use Modules\Shared\Infrastructure\Logging\LoggerFactory;

final class TestLoggerCommand extends Command
{
    protected $signature = 'test:logger';
    protected $description = 'Testa o sistema de logs estruturados';

    public function handle(): int
    {
        $this->info('Testando sistema de logs...');
        $this->newLine();

        // Test application logger
        $this->info('1. Logger de Aplicação');
        $logger = LoggerFactory::forModule('Test');
        $logger->info('Application log test', ['test_key' => 'test_value']);
        $this->line('Log gravado em: storage/logs/application.log');

        // Test domain logger
        $this->info('2. Logger de Domínio');
        $domainLogger = LoggerFactory::forDomain('Test');
        $domainLogger->event('TestEvent', ['event_data' => 'test']);
        $this->line('Log gravado em: storage/logs/domain.log');

        // Test infrastructure logger
        $this->info('3. Logger de Infraestrutura');
        $infraLogger = LoggerFactory::forInfrastructure('Test');
        $infraLogger->debug('Database query executed', ['duration_ms' => 45]);
        $this->line('Log gravado em: storage/logs/infrastructure.log');

        // Test audit logger
        $this->info('4. Logger de Auditoria');
        $auditLogger = LoggerFactory::forAudit('Test');
        $auditLogger->audit('test_action', 'TestEntity', 'test-123', ['field' => 'value']);
        $this->line('Log gravado em: storage/logs/audit.log');

        // Test security logger
        $this->info('5. Logger de Segurança');
        $securityLogger = LoggerFactory::forSecurity('Test');
        $securityLogger->warning('Test security warning', ['ip' => '127.0.0.1']);
        $this->line('Log gravado em: storage/logs/security.log');

        // Test performance logger
        $this->info('6. Logger de Performance');
        $perfLogger = LoggerFactory::forPerformance('Test');
        $perfLogger->info('API call completed', ['endpoint' => '/test', 'duration_ms' => 123]);
        $this->line('Log gravado em: storage/logs/performance.log');

        // Test error logger
        $this->info('7. Logger de Erro');
        try {
            throw new \RuntimeException('Test exception');
        } catch (\Throwable $e) {
            $logger->error('Test error with exception', ['context' => 'test'], $e);
            $this->line('Log de erro gravado com stack trace');
        }

        $this->newLine();
        $this->info('Todos os logs foram gravados com sucesso!');
        $this->newLine();
        $this->comment('Verifique os logs em storage/logs/');

        return self::SUCCESS;
    }
}
