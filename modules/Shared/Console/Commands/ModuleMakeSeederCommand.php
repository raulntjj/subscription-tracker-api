<?php

declare(strict_types=1);

namespace Modules\Shared\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class ModuleMakeSeederCommand extends Command
{
    protected $signature = 'module:make:seeder 
                            {name : Nome do seeder (ex: UserSeeder ou User)} 
                            {module : Nome do módulo (ex: User)}';

    protected $description = 'Cria um seeder na pasta correta do módulo especificado';

    public function handle(): int
    {
        $name = $this->argument('name');
        $module = Str::studly($this->argument('module'));

        $modulePath = base_path("modules/{$module}");

        if (!is_dir($modulePath)) {
            $this->error("Módulo '{$module}' não encontrado!");
            return self::FAILURE;
        }

        $seedersPath = "{$modulePath}/Infrastructure/Persistence/Seeders";

        if (!is_dir($seedersPath)) {
            mkdir($seedersPath, 0755, true);
            $this->info("Criado diretório: {$seedersPath}");
        }

        // Garante que o nome termina com "Seeder"
        $className = Str::studly($name);
        if (!Str::endsWith($className, 'Seeder')) {
            $className .= 'Seeder';
        }

        $filename = "{$className}.php";
        $filepath = "{$seedersPath}/{$filename}";

        if (file_exists($filepath)) {
            $this->error("Seeder já existe: {$filename}");
            return self::FAILURE;
        }

        $content = $this->getSeederStub($className, $module);

        file_put_contents($filepath, $content);

        $this->info("Seeder criado com sucesso!");
        $this->line("Arquivo: modules/{$module}/Infrastructure/Persistence/Seeders/{$filename}");
        $this->line("Classe: {$className}");
        $this->newLine();
        $this->comment("Para executar:");
        $this->line("php artisan module:seed {$module}");
        $this->line("php artisan module:seed {$module} --class={$className}");

        return self::SUCCESS;
    }

    private function getSeederStub(string $className, string $module): string
    {
        $modelName = str_replace('Seeder', '', $className);

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace Modules\\{$module}\\Infrastructure\\Persistence\\Seeders;

        use Illuminate\Database\Seeder;
        use Illuminate\Support\Facades\DB;
        use Modules\\{$module}\\Infrastructure\\Persistence\\Eloquent\\{$modelName}Model;

        final class {$className} extends Seeder
        {
            /**
             * Execute o seeder.
             */
            public function run(): void
            {
            }
        }

        PHP;
    }
}
