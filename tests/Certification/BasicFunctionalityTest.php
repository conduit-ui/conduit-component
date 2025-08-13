<?php

describe('Basic Functionality Certification', function () {
    it('can execute the component binary without errors', function () {
        $process = shell_exec('./component --version 2>&1');
        expect($process)->not()->toBeNull();
        expect($process)->not()->toContain('Fatal error');
        expect($process)->not()->toContain('Parse error');
    });

    it('can list all available commands', function () {
        $this->artisan('list')
            ->assertExitCode(0);
    });

    it('shows help information for the application', function () {
        $this->artisan('--help')
            ->assertExitCode(0);
    });

    it('displays version information', function () {
        $this->artisan('--version')
            ->assertExitCode(0);
    });

    it('can execute all discovered commands without fatal errors', function () {
        // Get list of commands
        $result = $this->artisan('list');
        $output = $result->getDisplay();

        // Extract command names from output
        preg_match_all('/^\s*([a-z:]+)\s+/m', $output, $matches);
        $commands = $matches[1] ?? [];

        // Filter out system commands that might require input
        $excludeCommands = ['help', 'completion', 'list'];
        $testableCommands = array_diff($commands, $excludeCommands);

        foreach ($testableCommands as $command) {
            if (! empty($command)) {
                // Test that command can be called with --help without error
                $helpResult = $this->artisan("$command --help");
                expect($helpResult->getExitCode())->toBeLessThan(2, "Command '$command' should handle --help gracefully");
            }
        }
    });

    it('has proper command registration', function () {
        $commandsConfig = require base_path('config/commands.php');

        expect($commandsConfig)->toBeArray();
        expect($commandsConfig)->toHaveKeys(['default', 'paths', 'add', 'hidden', 'published', 'remove']);
    });

    it('can run test command successfully', function () {
        $this->artisan('test')
            ->assertExitCode(0);
    });

    it('validates component structure integrity', function () {
        // Check that essential files exist
        $essentialFiles = [
            'app/Commands',
            'app/Contracts',
            'config/commands.php',
            'config/app.php',
            'composer.json',
            'tests',
        ];

        foreach ($essentialFiles as $file) {
            expect(file_exists(base_path($file)))->toBeTrue("Essential file/directory '$file' should exist");
        }
    });
});
