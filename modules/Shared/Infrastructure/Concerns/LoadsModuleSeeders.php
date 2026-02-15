<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Concerns;

use Illuminate\Support\Facades\File;

trait LoadsModuleSeeders
{
    /**
     * Registra os seeders do módulo para serem descobertos pelo db:seed
     *
     * @param string $path Caminho absoluto para a pasta de seeders
     * @return void
     */
    protected function loadSeedersFrom(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        // Registra o namespace dos seeders no container
        $this->app->beforeResolving('seed', function () use ($path) {
            $this->registerModuleSeeders($path);
        });

        // Também registra quando o comando db:seed é executado
        if ($this->app->runningInConsole()) {
            $this->callAfterResolving('Illuminate\Database\Console\Seeds\SeedCommand', function ($command) use ($path) {
                $this->registerModuleSeeders($path);
            });
        }
    }

    /**
     * Registra os seeders do módulo
     *
     * @param string $path
     * @return void
     */
    private function registerModuleSeeders(string $path): void
    {
        $seederFiles = File::glob("{$path}/*Seeder.php");

        foreach ($seederFiles as $seederFile) {
            $className = $this->getSeederClassNameFromFile($seederFile);
            
            if ($className && class_exists($className)) {
                // Registra o seeder no container para que possa ser chamado
                $this->app->singleton($className, function ($app) use ($className) {
                    return new $className();
                });
            }
        }
    }

    /**
     * Extrai o nome da classe do seeder a partir do caminho do arquivo
     *
     * @param string $filePath
     * @return string|null
     */
    private function getSeederClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        
        // Extrai o namespace
        if (preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches)) {
            $namespace = $namespaceMatches[1];
            
            // Extrai o nome da classe
            if (preg_match('/class\s+(\w+)/', $content, $classMatches)) {
                $className = $classMatches[1];
                return "{$namespace}\\{$className}";
            }
        }
        
        return null;
    }
}
