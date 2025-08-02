<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Application as Artisan;
use App\Contracts\CommandInterface;
use Symfony\Component\Finder\Finder;
use ReflectionClass;

class CommandRegistrationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Commands are automatically discovered through config/commands.php
        // This provider can be used for additional command-related services
    }

    /**
     * Dynamically register commands with advanced introspection.
     */
    protected function registerDynamicCommands(): void
    {
        $commandPaths = [
            app_path('Commands'),
            // Add additional paths for command discovery
        ];

        foreach ($commandPaths as $path) {
            $namespace = 'App\\Commands';
            $finder = new Finder();
            $finder->in($path)->name('*Command.php');

            foreach ($finder as $file) {
                $relativePath = str_replace([$path, '.php'], '', $file->getRealPath());
                $className = $namespace . str_replace('/', '\\', $relativePath);

                if (class_exists($className)) {
                    $reflection = new ReflectionClass($className);
                    
                    // Only register concrete commands that implement CommandInterface
                    if (!$reflection->isAbstract() && $reflection->implementsInterface(CommandInterface::class)) {
                        $command = $this->app->make($className);
                        
                        // Dynamic publishability check
                        if ($command->isPublishable()) {
                            $this->app->make(Artisan::class)->add($command);
                            $this->logCommandRegistration($command);
                        }
                    }
                }
            }
        }
    }

    /**
     * Log command registration details.
     */
    protected function logCommandRegistration(CommandInterface $command): void
    {
        $knowledgeMetadata = $command->captureKnowledge();
        $liberationMetrics = $command->getLiberationMetrics();

        // Optional: Implement more sophisticated knowledge capture
        info('Command Registered', [
            'name' => $knowledgeMetadata['command'],
            'description' => $knowledgeMetadata['description'],
            'liberation_metrics' => $liberationMetrics,
        ]);
    }
}