<?php

describe('Component Standards Certification', function () {
    it('validates commands.php configuration structure', function () {
        $commandsConfig = require base_path('config/commands.php');

        expect($commandsConfig)->toBeArray('Commands config should be an array');

        // Required keys for conduit compatibility
        $requiredKeys = ['default', 'paths', 'add', 'hidden', 'published', 'remove'];

        foreach ($requiredKeys as $key) {
            expect($commandsConfig)->toHaveKey($key, "Commands config should have '$key' key");
        }

        // Validate data types
        expect($commandsConfig['paths'])->toBeArray('paths should be an array');
        expect($commandsConfig['add'])->toBeArray('add should be an array');
        expect($commandsConfig['hidden'])->toBeArray('hidden should be an array');
        expect($commandsConfig['published'])->toBeArray('published should be an array');
        expect($commandsConfig['remove'])->toBeArray('remove should be an array');
    });

    it('validates default command configuration', function () {
        $commandsConfig = require base_path('config/commands.php');

        expect($commandsConfig['default'])->not()->toBeNull('Default command should be set');
        expect($commandsConfig['default'])->toBeString('Default command should be a string');

        // Default command should be a valid class
        if (class_exists($commandsConfig['default'])) {
            $reflection = new ReflectionClass($commandsConfig['default']);
            expect($reflection->isSubclassOf('Illuminate\Console\Command'))
                ->toBeTrue('Default command should extend Illuminate\Console\Command');
        }
    });

    it('validates command paths configuration', function () {
        $commandsConfig = require base_path('config/commands.php');
        $paths = $commandsConfig['paths'];

        expect($paths)->not()->toBeEmpty('Command paths should not be empty');

        foreach ($paths as $path) {
            expect($path)->toBeString('Each path should be a string');

            // Resolve the path
            if (function_exists('app_path') && str_contains($path, 'app_path(')) {
                $resolvedPath = eval("return $path;");
                expect(is_dir($resolvedPath))->toBeTrue("Command path should exist: $resolvedPath");
            } elseif (str_starts_with($path, '/')) {
                expect(is_dir($path))->toBeTrue("Absolute command path should exist: $path");
            } else {
                $fullPath = base_path($path);
                expect(is_dir($fullPath))->toBeTrue("Relative command path should exist: $fullPath");
            }
        }
    });

    it('validates published commands structure', function () {
        $commandsConfig = require base_path('config/commands.php');
        $published = $commandsConfig['published'];

        expect($published)->toBeArray('Published commands should be an array');

        // If there are published commands, validate their structure
        foreach ($published as $command) {
            if (is_string($command)) {
                // Command class name
                expect(class_exists($command))->toBeTrue("Published command class should exist: $command");
            } elseif (is_array($command)) {
                // Command configuration array
                expect($command)->toHaveKey('class', 'Published command config should have class key');
                expect(class_exists($command['class']))->toBeTrue("Published command class should exist: {$command['class']}");
            }
        }
    });

    it('validates hidden commands configuration', function () {
        $commandsConfig = require base_path('config/commands.php');
        $hidden = $commandsConfig['hidden'];

        expect($hidden)->toBeArray('Hidden commands should be an array');

        foreach ($hidden as $command) {
            expect($command)->toBeString('Hidden command should be a string (class name)');

            // Command should exist or be a system command
            $systemCommands = [
                'Symfony\Component\Console\Command\DumpCompletionCommand',
                'Symfony\Component\Console\Command\HelpCommand',
                'Illuminate\Console\Scheduling\ScheduleRunCommand',
                'Illuminate\Console\Scheduling\ScheduleListCommand',
                'Illuminate\Console\Scheduling\ScheduleFinishCommand',
                'Illuminate\Foundation\Console\VendorPublishCommand',
            ];

            if (! in_array($command, $systemCommands)) {
                expect(class_exists($command))->toBeTrue("Hidden command class should exist: $command");
            }
        }
    });

    it('validates app.php configuration essentials', function () {
        $appConfig = require base_path('config/app.php');

        expect($appConfig)->toBeArray('App config should be an array');

        // Essential Laravel Zero configuration
        $essentialKeys = ['name', 'version', 'env', 'debug'];

        foreach ($essentialKeys as $key) {
            expect($appConfig)->toHaveKey($key, "App config should have '$key' key");
        }

        // Validate data types
        expect($appConfig['name'])->toBeString('App name should be a string');
        expect($appConfig['version'])->toBeString('App version should be a string');
        expect($appConfig['env'])->toBeString('App env should be a string');
        expect($appConfig['debug'])->toBeBoolean('App debug should be a boolean');
    });

    it('ensures proper command registration patterns', function () {
        // Test that commands can be discovered through the configured paths
        $commandsConfig = require base_path('config/commands.php');
        $paths = $commandsConfig['paths'];

        $foundCommands = [];

        foreach ($paths as $path) {
            if (function_exists('app_path') && str_contains($path, 'app_path(')) {
                $resolvedPath = eval("return $path;");
            } else {
                $resolvedPath = base_path($path);
            }

            if (is_dir($resolvedPath)) {
                $files = glob($resolvedPath.'/*.php');

                foreach ($files as $file) {
                    $content = file_get_contents($file);

                    // Look for command class patterns
                    if (preg_match('/class\s+(\w+)\s+extends\s+(Command|BaseCommand)/', $content, $matches)) {
                        $foundCommands[] = $matches[1];
                    }
                }
            }
        }

        expect(count($foundCommands))->toBeGreaterThan(0, 'Should discover at least one command through configured paths');
    });

    it('validates component follows Laravel Zero conventions', function () {
        // Check composer.json follows Laravel Zero patterns
        $composerData = json_decode(file_get_contents(base_path('composer.json')), true);

        expect($composerData['require'])->toHaveKey('laravel-zero/framework', 'Should require Laravel Zero framework');

        // Check for binary
        expect($composerData)->toHaveKey('bin', 'Should define binary in composer.json');
        expect($composerData['bin'])->toBeArray('Binary should be an array');
        expect(count($composerData['bin']))->toBeGreaterThan(0, 'Should have at least one binary');
    });

    it('ensures configuration supports both standalone and conduit modes', function () {
        $commandsConfig = require base_path('config/commands.php');

        // Should have paths for standalone discovery
        expect($commandsConfig['paths'])->not()->toBeEmpty('Should have command paths for standalone mode');

        // Should have published array for conduit integration
        expect($commandsConfig)->toHaveKey('published', 'Should have published commands for conduit integration');

        // Should have proper default command for standalone execution
        expect($commandsConfig['default'])->not()->toBeNull('Should have default command for standalone mode');
    });

    it('validates environment configuration consistency', function () {
        $appConfig = require base_path('config/app.php');

        // Environment should be properly configured
        expect($appConfig['env'])->toBeIn(['local', 'testing', 'production'], 'Environment should be valid');

        // Debug setting should align with environment
        if ($appConfig['env'] === 'production') {
            expect($appConfig['debug'])->toBeFalse('Debug should be false in production');
        }
    });

    it('ensures proper service provider registration', function () {
        $appConfig = require base_path('config/app.php');

        if (isset($appConfig['providers'])) {
            $providers = $appConfig['providers'];
            expect($providers)->toBeArray('Providers should be an array');

            foreach ($providers as $provider) {
                expect($provider)->toBeString('Provider should be a string (class name)');
                expect(class_exists($provider))->toBeTrue("Provider class should exist: $provider");
            }
        }
    });
});
