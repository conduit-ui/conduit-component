<?php

use App\Contracts\CommandInterface;
use Illuminate\Support\Facades\File;

describe('Conduit Integration Certification', function () {
    it('has publishable commands configuration', function () {
        $commandsConfig = require base_path('config/commands.php');

        expect($commandsConfig)->toHaveKey('published');
        expect($commandsConfig['published'])->toBeArray();
    });

    it('validates all commands implement CommandInterface when using BaseCommand', function () {
        $commandPath = app_path('Commands');

        if (! File::exists($commandPath)) {
            $this->markTestSkipped('No commands directory found');
        }

        $commandFiles = File::glob($commandPath.'/*.php');

        foreach ($commandFiles as $file) {
            $className = 'App\\Commands\\'.pathinfo($file, PATHINFO_FILENAME);

            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);

                // If it extends BaseCommand, it should implement CommandInterface
                if ($reflection->isSubclassOf('App\\Commands\\BaseCommand')) {
                    expect($reflection->implementsInterface(CommandInterface::class))
                        ->toBeTrue("Command {$className} should implement CommandInterface");
                }
            }
        }
    });

    it('verifies commands can capture knowledge metadata', function () {
        $commandPath = app_path('Commands');

        if (! File::exists($commandPath)) {
            $this->markTestSkipped('No commands directory found');
        }

        $commandFiles = File::glob($commandPath.'/*.php');

        foreach ($commandFiles as $file) {
            $className = 'App\\Commands\\'.pathinfo($file, PATHINFO_FILENAME);

            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);

                if ($reflection->implementsInterface(CommandInterface::class) && ! $reflection->isAbstract()) {
                    $command = $reflection->newInstance();
                    $knowledge = $command->captureKnowledge();

                    expect($knowledge)->toBeArray("Knowledge capture should return array for {$className}");
                    expect($knowledge)->toHaveKey('command', "Knowledge should include command name for {$className}");
                    expect($knowledge)->toHaveKey('description', "Knowledge should include description for {$className}");
                    expect($knowledge)->toHaveKey('tags', "Knowledge should include tags for {$className}");
                }
            }
        }
    });

    it('validates publishability interface methods', function () {
        $commandPath = app_path('Commands');

        if (! File::exists($commandPath)) {
            $this->markTestSkipped('No commands directory found');
        }

        $commandFiles = File::glob($commandPath.'/*.php');

        foreach ($commandFiles as $file) {
            $className = 'App\\Commands\\'.pathinfo($file, PATHINFO_FILENAME);

            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);

                if ($reflection->implementsInterface(CommandInterface::class) && ! $reflection->isAbstract()) {
                    $command = $reflection->newInstance();

                    expect(is_bool($command->isPublishable()))
                        ->toBeTrue("isPublishable() should return boolean for {$className}");

                    $metrics = $command->getLiberationMetrics();
                    expect($metrics)->toBeArray("Liberation metrics should be array for {$className}");
                }
            }
        }
    });

    it('checks for conduit binary availability when installed', function () {
        // This test checks if conduit is available, but doesn't fail if it's not
        $conduitAvailable = ! empty(shell_exec('which conduit 2>/dev/null'));

        if ($conduitAvailable) {
            $result = shell_exec('conduit --version 2>&1');
            expect($result)->not()->toBeNull();
            expect($result)->not()->toContain('command not found');
        } else {
            // Log that conduit is not available for testing
            $this->markTestSkipped('Conduit binary not available for integration testing');
        }
    });

    it('validates component can be registered with conduit ecosystem', function () {
        // Test that the component follows the expected structure for conduit registration
        $composerData = json_decode(file_get_contents(base_path('composer.json')), true);

        expect($composerData)->toHaveKey('bin');
        expect($composerData['bin'])->toContain('component');

        // Check that the component binary is executable
        $componentPath = base_path('component');
        expect(file_exists($componentPath))->toBeTrue('Component binary should exist');
        expect(is_executable($componentPath))->toBeTrue('Component binary should be executable');
    });

    it('ensures dual-mode operation compatibility', function () {
        // Test that commands can run both standalone and through conduit delegation
        $commandsConfig = require base_path('config/commands.php');

        // Check that there are mechanisms for both standalone and delegated execution
        expect($commandsConfig)->toHaveKey('published', 'Published commands key should exist for conduit integration');
        expect($commandsConfig)->toHaveKey('paths', 'Command paths should be defined for standalone operation');
    });
});
