<?php

declare(strict_types=1);

namespace Modules\Shared\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class ModuleMakeMigrationCommand extends Command
{
    protected $signature = 'module:make:migration 
                            {name : Nome da migration (ex: create_users_table)} 
                            {module : Nome do módulo (ex: User)}';

    protected $description = 'Cria uma migration na pasta correta do módulo especificado';

    public function handle(): int
    {
        $name = $this->argument('name');
        $module = Str::studly($this->argument('module'));

        $modulePath = base_path("modules/{$module}");

        if (!is_dir($modulePath)) {
            $this->error("❌ Módulo '{$module}' não encontrado!");
            return self::FAILURE;
        }

        $migrationsPath = "{$modulePath}/Infrastructure/Persistence/Migrations";

        if (!is_dir($migrationsPath)) {
            mkdir($migrationsPath, 0755, true);
            $this->info("📁 Criado diretório: {$migrationsPath}");
        }

        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.php";
        $filepath = "{$migrationsPath}/{$filename}";

        $className = Str::studly($name);
        $tableName = $this->extractTableName($name);

        $content = $this->getMigrationStub($className, $tableName);

        file_put_contents($filepath, $content);

        $this->info("✅ Migration criada com sucesso!");
        $this->line("📄 Arquivo: modules/{$module}/Infrastructure/Persistence/Migrations/{$filename}");
        $this->line("🏷️  Classe: {$className}");

        return self::SUCCESS;
    }

    private function extractTableName(string $name): string
    {
        // Extrai o nome da tabela de padrões como "create_users_table" ou "add_column_to_users"
        if (preg_match('/create_(\w+)_table/', $name, $matches)) {
            return $matches[1];
        }

        if (preg_match('/to_(\w+)/', $name, $matches)) {
            return $matches[1];
        }

        return 'table_name';
    }

    private function getMigrationStub(string $className, string $tableName): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        use Illuminate\Database\Migrations\Migration;
        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\Schema;

        return new class extends Migration
        {
            public function up(): void
            {
                Schema::create('{$tableName}', function (Blueprint \$table) {
                    \$table->uuid('id')->primary();
                    \$table->timestamps();
                });
            }

            public function down(): void
            {
                Schema::dropIfExists('{$tableName}');
            }
        };

        PHP;
    }
}
