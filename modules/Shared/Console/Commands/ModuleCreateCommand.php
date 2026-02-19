<?php

declare(strict_types=1);

namespace Modules\Shared\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;

final class ModuleCreateCommand extends Command
{
    protected $signature = 'module:create 
                            {name : Nome do módulo (ex: Company)}';

    protected $description = 'Cria a estrutura completa de um novo módulo DDD a partir de stubs';

    /**
     * Caminho base dos stubs
     */
    private string $stubsPath;

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $this->stubsPath = __DIR__ . '/../Stubs/module';

        $modulePath = base_path("modules/{$name}");

        if (is_dir($modulePath)) {
            $this->error("Módulo '{$name}' já existe!");
            return self::FAILURE;
        }

        $this->info("Criando módulo {$name}...");
        $this->newLine();

        // Mapeamento de placeholders
        $replacements = $this->buildReplacements($name);

        // Gera os arquivos a partir dos stubs
        $this->generateFromStubs($modulePath, $replacements, $name);

        // Cria diretórios vazios com .gitkeep
        $this->createEmptyDirectories($modulePath);

        $this->newLine();
        $this->info("Módulo '{$name}' criado com sucesso!");
        $this->line("Localização: modules/{$name}");
        $this->newLine();

        $this->displayEndpoints($replacements);
        $this->displayNextSteps($name);

        return self::SUCCESS;
    }

    /**
     * Constrói o array de placeholders => valores
     */
    private function buildReplacements(string $name): array
    {
        $plural = Str::plural($name);
        $snakeName = Str::snake($name);
        $snakePlural = Str::snake($plural);

        return [
            '{{MODULE_NAME}}' => $name,
            '{{MODULE_NAME_LOWER}}' => Str::lower($name),
            '{{MODULE_NAME_PLURAL}}' => $plural,
            '{{MODULE_NAME_SNAKE}}' => $snakeName,
            '{{MODULE_NAME_SNAKE_PLURAL}}' => $snakePlural,
        ];
    }

    /**
     * Percorre stubs e gera arquivos finais
     */
    private function generateFromStubs(string $modulePath, array $replacements, string $name): void
    {
        $stubFiles = $this->getStubFileMap($name);

        foreach ($stubFiles as $stubRelativePath => $targetRelativePath) {
            $stubFullPath = $this->stubsPath . '/' . $stubRelativePath;

            if (!file_exists($stubFullPath)) {
                $this->warn("Stub não encontrado: {$stubRelativePath}");
                continue;
            }

            $content = file_get_contents($stubFullPath);
            $content = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $content
            );

            $targetFullPath = $modulePath . '/' . $targetRelativePath;
            $targetDir = dirname($targetFullPath);

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            file_put_contents($targetFullPath, $content);

            $relativePath = str_replace(base_path() . '/', '', $targetFullPath);
            $this->line("{$relativePath}");
        }
    }

    /**
     * Mapeamento: stub path => target path
     */
    private function getStubFileMap(string $name): array
    {
        $plural = Str::plural($name);
        $snakePlural = Str::snake($plural);
        $migrationDate = date('Y_m_d_His');

        return [
            // Domain
            'Domain/Entities/Entity.stub'
                => "Domain/Entities/{$name}.php",
            'Domain/Contracts/RepositoryInterface.stub'
                => "Domain/Contracts/{$name}RepositoryInterface.php",

            // Application - DTOs
            'Application/DTOs/EntityDTO.stub'
                => "Application/DTOs/{$name}DTO.php",
            'Application/DTOs/CreateEntityDTO.stub'
                => "Application/DTOs/Create{$name}DTO.php",
            'Application/DTOs/UpdateEntityDTO.stub'
                => "Application/DTOs/Update{$name}DTO.php",

            // Application - UseCases
            'Application/UseCases/CreateEntityUseCase.stub'
                => "Application/UseCases/Create{$name}UseCase.php",
            'Application/UseCases/UpdateEntityUseCase.stub'
                => "Application/UseCases/Update{$name}UseCase.php",
            'Application/UseCases/PartialUpdateEntityUseCase.stub'
                => "Application/UseCases/PartialUpdate{$name}UseCase.php",
            'Application/UseCases/DeleteEntityUseCase.stub'
                => "Application/UseCases/Delete{$name}UseCase.php",

            // Application - Queries
            'Application/Queries/FindEntityByIdQuery.stub'
                => "Application/Queries/Find{$name}ByIdQuery.php",
            'Application/Queries/FindEntityOptionsQuery.stub'
                => "Application/Queries/Find{$name}OptionsQuery.php",
            'Application/Queries/FindEntitiesPaginatedQuery.stub'
                => "Application/Queries/Find{$plural}PaginatedQuery.php",
            'Application/Queries/FindEntitiesCursorPaginatedQuery.stub'
                => "Application/Queries/Find{$plural}CursorPaginatedQuery.php",

            // Infrastructure
            'Infrastructure/Persistence/Eloquent/EntityModel.stub'
                => "Infrastructure/Persistence/Eloquent/{$name}Model.php",
            'Infrastructure/Persistence/EntityRepository.stub'
                => "Infrastructure/Persistence/{$name}Repository.php",
            'Infrastructure/Persistence/Migrations/create_table.stub'
                => "Infrastructure/Persistence/Migrations/{$migrationDate}_create_{$snakePlural}_table.php",
            'Infrastructure/Providers/ServiceProvider.stub'
                => "Infrastructure/Providers/{$name}ServiceProvider.php",

            // Interface
            'Interface/Http/Controllers/WebController.stub'
                => "Interface/Http/Controllers/{$name}Controller.php",
            'Interface/Http/Controllers/MobileController.stub'
                => "Interface/Http/Controllers/Mobile{$name}Controller.php",
            'Interface/Routes/web.stub'
                => 'Interface/Routes/web.php',
            'Interface/Routes/mobile.stub'
                => 'Interface/Routes/mobile.php',
        ];
    }

    /**
     * Cria diretórios vazios com .gitkeep
     */
    private function createEmptyDirectories(string $modulePath): void
    {
        $emptyDirs = [
            'Domain/ValueObjects',
            'Infrastructure/Persistence/Seeders',
            'Tests/Unit',
            'Tests/Feature',
            'Tests/Integration',
        ];

        foreach ($emptyDirs as $dir) {
            $fullPath = $modulePath . '/' . $dir;

            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            $gitkeep = $fullPath . '/.gitkeep';
            if (!file_exists($gitkeep)) {
                file_put_contents($gitkeep, '');
            }
        }
    }

    /**
     * Exibe os endpoints que serão criados
     */
    private function displayEndpoints(array $replacements): void
    {
        $prefix = $replacements['{{MODULE_NAME_SNAKE_PLURAL}}'];
        $name = $replacements['{{MODULE_NAME}}'];

        $this->info("Endpoints disponíveis:");
        $this->newLine();

        $this->line("  <fg=cyan>Web (offset pagination):</>");
        $this->line("  GET    /api/web/v1/{$prefix}                → {$name}Controller@index");
        $this->line("  GET    /api/web/v1/{$prefix}/options        → {$name}Controller@options");
        $this->line("  GET    /api/web/v1/{$prefix}/{id}           → {$name}Controller@show");
        $this->line("  POST   /api/web/v1/{$prefix}                → {$name}Controller@store");
        $this->line("  PUT    /api/web/v1/{$prefix}/{id}           → {$name}Controller@update");
        $this->line("  PATCH  /api/web/v1/{$prefix}/{id}           → {$name}Controller@partialUpdate");
        $this->line("  DELETE /api/web/v1/{$prefix}/{id}           → {$name}Controller@destroy");
        $this->newLine();

        $this->line("  <fg=cyan>Mobile (cursor pagination):</>");
        $this->line("  GET    /api/mobile/v1/{$prefix}             → Mobile{$name}Controller@index");
        $this->line("  GET    /api/mobile/v1/{$prefix}/options     → Mobile{$name}Controller@options");
        $this->newLine();
    }

    /**
     * Exibe os próximos passos
     */
    private function displayNextSteps(string $name): void
    {
        $this->warn("Próximos passos:");
        $this->newLine();
        $this->line("  1. Registre o ServiceProvider em <fg=yellow>bootstrap/providers.php</>:");
        $this->line("     <fg=green>Modules\\{$name}\\Infrastructure\\Providers\\{$name}ServiceProvider::class,</>");
        $this->newLine();
        $this->line("  2. Adicione o namespace no <fg=yellow>composer.json</> (autoload.psr-4):");
        $this->line("     <fg=green>\"Modules\\\\{$name}\\\\\": \"modules/{$name}/\"</>");
        $this->newLine();
        $this->line("  3. Execute: <fg=green>composer dump-autoload</>");
        $this->newLine();
        $this->line("  4. Execute as migrations: <fg=green>php artisan migrate</>");
        $this->newLine();
        $this->line("  5. Personalize a entidade, DTOs e validações conforme necessário.");
        $this->newLine();
    }
}
