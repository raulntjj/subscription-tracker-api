<?php

declare(strict_types=1);

namespace Modules\Shared\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class ModuleSeedCommand extends Command
{
    protected $signature = 'module:seed
                            {module? : Nome do módulo específico para executar seeders (opcional)} 
                            {--class= : Classe do seeder específico para executar}
                            {--force : Força a execução em ambiente de produção}';

    protected $description = 'Executa os seeders dos módulos (similar ao migrate para migrations)';

    private array $seededClasses = [];

    public function handle(): int
    {
        $module = $this->argument('module');
        $class = $this->option('class');

        if (!$this->confirmToProceed()) {
            return self::FAILURE;
        }

        $this->info('Executando seeders dos módulos...');
        $this->newLine();

        if ($module) {
            return $this->seedModule($module, $class);
        }

        return $this->seedAllModules();
    }

    private function seedModule(string $moduleName, ?string $class = null): int
    {
        $module = Str::studly($moduleName);
        $modulePath = base_path("modules/{$module}");

        if (!is_dir($modulePath)) {
            $this->error("Módulo '{$module}' não encontrado!");
            return self::FAILURE;
        }

        $seedersPath = "{$modulePath}/Infrastructure/Persistence/Seeders";

        if (!is_dir($seedersPath)) {
            $this->warn("Diretório de seeders não encontrado em: {$module}/Infrastructure/Persistence/Seeders");
            return self::SUCCESS;
        }

        if ($class) {
            return $this->runSpecificSeeder($module, $class);
        }

        return $this->runModuleSeeders($module, $seedersPath);
    }

    private function seedAllModules(): int
    {
        $modulesPath = base_path('modules');
        $modules = File::directories($modulesPath);

        if (empty($modules)) {
            $this->warn('Nenhum módulo encontrado!');
            return self::SUCCESS;
        }

        $seededCount = 0;
        $skippedCount = 0;

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);

            // Pula o módulo Shared
            if ($moduleName === 'Shared') {
                continue;
            }

            $seedersPath = "{$modulePath}/Infrastructure/Persistence/Seeders";

            if (!is_dir($seedersPath)) {
                $this->comment("{$moduleName}: Sem seeders");
                $skippedCount++;
                continue;
            }

            $this->info("Módulo: {$moduleName}");

            $result = $this->runModuleSeeders($moduleName, $seedersPath);

            if ($result === self::SUCCESS) {
                $seededCount++;
            }

            $this->newLine();
        }

        $this->newLine();
        $this->info("Seeders executados: {$seededCount} módulo(s)");

        if ($skippedCount > 0) {
            $this->comment("Módulos sem seeders: {$skippedCount}");
        }

        if (!empty($this->seededClasses)) {
            $this->newLine();
            $this->info('Classes executadas:');
            foreach ($this->seededClasses as $class) {
                $this->line("   • {$class}");
            }
        }

        return self::SUCCESS;
    }

    private function runModuleSeeders(string $module, string $seedersPath): int
    {
        $seederFiles = File::glob("{$seedersPath}/*Seeder.php");

        if (empty($seederFiles)) {
            $this->comment("Nenhum seeder encontrado");
            return self::SUCCESS;
        }

        // Ordena os seeders para executar DatabaseSeeder por último (se existir)
        usort($seederFiles, function ($a, $b) {
            $aIsDatabaseSeeder = str_contains($a, 'DatabaseSeeder');
            $bIsDatabaseSeeder = str_contains($b, 'DatabaseSeeder');

            if ($aIsDatabaseSeeder && !$bIsDatabaseSeeder) {
                return 1;
            }
            if (!$aIsDatabaseSeeder && $bIsDatabaseSeeder) {
                return -1;
            }

            return strcmp($a, $b);
        });

        foreach ($seederFiles as $seederFile) {
            $className = $this->getSeederClassName($module, $seederFile);

            if (!$className) {
                $this->warn("Não foi possível determinar a classe: " . basename($seederFile));
                continue;
            }

            if (!class_exists($className)) {
                $this->warn("Classe não encontrada: {$className}");
                continue;
            }

            $this->executeSeeder($className);
        }

        return self::SUCCESS;
    }

    private function runSpecificSeeder(string $module, string $class): int
    {
        // Tenta diferentes formatos de classe
        $possibleClasses = [
            $class, // Nome completo fornecido
            "Modules\\{$module}\\Infrastructure\\Persistence\\Seeders\\{$class}", // Nome curto
            "Modules\\{$module}\\Infrastructure\\Persistence\\Seeders\\{$class}Seeder", // Nome sem sufixo
        ];

        foreach ($possibleClasses as $className) {
            if (class_exists($className)) {
                $this->info("Módulo: {$module}");
                $this->executeSeeder($className);
                return self::SUCCESS;
            }
        }

        $this->error("Seeder não encontrado: {$class}");
        $this->comment("Tentou procurar em:");
        foreach ($possibleClasses as $tried) {
            $this->line("   • {$tried}");
        }

        return self::FAILURE;
    }

    private function executeSeeder(string $className): void
    {
        $seederName = class_basename($className);

        try {
            $this->line("Executando: {$seederName}...");

            $seeder = $this->laravel->make($className);

            // Suporta tanto run() quanto __invoke()
            if (method_exists($seeder, 'run')) {
                $seeder->run();
            } elseif (method_exists($seeder, '__invoke')) {
                $seeder->__invoke();
            }

            $this->seededClasses[] = $className;
            $this->info("{$seederName} executado com sucesso!");

        } catch (\Throwable $e) {
            $this->error("Erro ao executar {$seederName}:");
            $this->error("{$e->getMessage()}");
            if ($this->output->isVerbose()) {
                $this->newLine();
                $this->line($e->getTraceAsString());
            }
        }
    }

    private function getSeederClassName(string $module, string $filePath): ?string
    {
        $filename = basename($filePath, '.php');

        // Remove qualquer prefixo numérico de ordem (ex: 01_UserSeeder.php)
        $filename = preg_replace('/^\d+_/', '', $filename);

        return "Modules\\{$module}\\Infrastructure\\Persistence\\Seeders\\{$filename}";
    }

    private function confirmToProceed(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        if ($this->laravel->environment() === 'production') {
            $this->warn('Aplicação está em ambiente de PRODUÇÃO!');

            return $this->confirm('Deseja realmente executar os seeders em produção?', false);
        }

        return true;
    }
}
