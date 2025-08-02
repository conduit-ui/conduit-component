<?php

use Tests\TestCase;
use Illuminate\Support\Facades\File;

describe('Architecture Validation Certification', function () {
    it('validates required directory structure exists', function () {
        $requiredDirectories = [
            'app',
            'app/Commands',
            'app/Contracts', 
            'config',
            'tests',
            'tests/Feature',
            'tests/Unit'
        ];
        
        foreach ($requiredDirectories as $dir) {
            $path = base_path($dir);
            expect(is_dir($path))->toBeTrue("Required directory should exist: $dir");
        }
    });

    it('validates required configuration files exist', function () {
        $requiredFiles = [
            'config/app.php',
            'config/commands.php',
            'composer.json',
            'phpunit.xml.dist'
        ];
        
        foreach ($requiredFiles as $file) {
            $path = base_path($file);
            expect(file_exists($path))->toBeTrue("Required file should exist: $file");
        }
    });

    it('validates BaseCommand architecture', function () {
        $baseCommandPath = app_path('Commands/BaseCommand.php');
        expect(file_exists($baseCommandPath))->toBeTrue('BaseCommand should exist');
        
        // Validate BaseCommand implements CommandInterface
        $baseCommand = file_get_contents($baseCommandPath);
        expect($baseCommand)->toContain('implements CommandInterface');
        expect($baseCommand)->toContain('abstract class BaseCommand');
    });

    it('validates CommandInterface contract exists', function () {
        $contractPath = app_path('Contracts/CommandInterface.php');
        expect(file_exists($contractPath))->toBeTrue('CommandInterface contract should exist');
        
        $contract = file_get_contents($contractPath);
        expect($contract)->toContain('interface CommandInterface');
        expect($contract)->toContain('captureKnowledge');
        expect($contract)->toContain('isPublishable');
        expect($contract)->toContain('getLiberationMetrics');
    });

    it('validates service provider architecture', function () {
        $providerPath = app_path('Providers');
        expect(is_dir($providerPath))->toBeTrue('Providers directory should exist');
        
        // Check for service providers
        $providers = File::glob($providerPath . '/*.php');
        expect(count($providers))->toBeGreaterThan(0, 'Should have at least one service provider');
        
        foreach ($providers as $provider) {
            $content = file_get_contents($provider);
            expect($content)->toContain('extends ServiceProvider');
        }
    });

    it('validates bootstrap architecture', function () {
        $bootstrapPath = base_path('bootstrap');
        expect(is_dir($bootstrapPath))->toBeTrue('Bootstrap directory should exist');
        
        $requiredBootstrapFiles = ['app.php'];
        
        foreach ($requiredBootstrapFiles as $file) {
            $path = $bootstrapPath . '/' . $file;
            expect(file_exists($path))->toBeTrue("Bootstrap file should exist: $file");
        }
    });

    it('validates test architecture', function () {
        $testPath = base_path('tests');
        expect(is_dir($testPath))->toBeTrue('Tests directory should exist');
        
        // Check for Pest configuration
        $pestFile = $testPath . '/Pest.php';
        expect(file_exists($pestFile))->toBeTrue('Pest.php configuration should exist');
        
        // Check for TestCase
        $testCaseFile = $testPath . '/TestCase.php';
        expect(file_exists($testCaseFile))->toBeTrue('TestCase.php should exist');
        
        $testCase = file_get_contents($testCaseFile);
        expect($testCase)->toContain('abstract class TestCase');
    });

    it('validates namespace structure follows PSR-4', function () {
        $composerData = json_decode(file_get_contents(base_path('composer.json')), true);
        $psr4 = $composerData['autoload']['psr-4'];
        
        expect($psr4)->toHaveKey('App\\');
        expect($psr4['App\\'])->toBe('app/');
        
        // Validate actual directory structure matches namespace
        $appDir = base_path('app');
        $directories = File::directories($appDir);
        
        foreach ($directories as $dir) {
            $dirname = basename($dir);
            $expectedNamespace = "App\\$dirname";
            
            // Check if PHP files in directory use correct namespace
            $phpFiles = File::glob($dir . '/*.php');
            
            foreach ($phpFiles as $file) {
                $content = file_get_contents($file);
                expect($content)->toContain("namespace $expectedNamespace");
            }
        }
    });

    it('validates component binary architecture', function () {
        $componentBinary = base_path('component');
        expect(file_exists($componentBinary))->toBeTrue('Component binary should exist');
        expect(is_executable($componentBinary))->toBeTrue('Component binary should be executable');
        
        // Check that it's referenced in composer.json
        $composerData = json_decode(file_get_contents(base_path('composer.json')), true);
        expect($composerData)->toHaveKey('bin');
        expect($composerData['bin'])->toContain('component');
    });

    it('validates config architecture follows Laravel Zero patterns', function () {
        $configDir = base_path('config');
        $configs = File::files($configDir);
        
        foreach ($configs as $config) {
            if ($config->getExtension() === 'php') {
                $content = file_get_contents($config->getPathname());
                
                // Config files should return arrays
                expect($content)->toMatch('/return\s*\[/');
                
                // Should not have syntax errors
                $syntaxCheck = shell_exec('php -l "' . $config->getPathname() . '" 2>&1');
                expect($syntaxCheck)->toContain('No syntax errors detected');
            }
        }
    });

    it('validates command discovery patterns', function () {
        $commandsDir = app_path('Commands');
        
        if (is_dir($commandsDir)) {
            $commands = File::glob($commandsDir . '/*.php');
            
            foreach ($commands as $command) {
                $content = file_get_contents($command);
                
                // Commands should extend Command or BaseCommand
                $extendsPattern = '/extends\s+(Command|BaseCommand)/';
                expect($content)->toMatch($extendsPattern);
                
                // Should have signature property or method
                expect($content)->toMatch('/(\$signature\s*=|protected\s+function\s+signature)/');
            }
        }
    });

    it('validates dependency injection container setup', function () {
        $appBootstrap = base_path('bootstrap/app.php');
        
        if (file_exists($appBootstrap)) {
            $content = file_get_contents($appBootstrap);
            
            // Should create application instance
            expect($content)->toContain('Application');
            
            // Should return the application
            expect($content)->toContain('return $app');
        }
    });

    it('ensures proper error handling architecture', function () {
        // Check that commands have proper error handling patterns
        $commandsDir = app_path('Commands');
        
        if (is_dir($commandsDir)) {
            $commands = File::glob($commandsDir . '/*.php');
            
            foreach ($commands as $command) {
                $content = file_get_contents($command);
                
                // Commands should have handle method or similar
                expect($content)->toMatch('/(function\s+handle|function\s+__invoke)/');
            }
        }
    });
});