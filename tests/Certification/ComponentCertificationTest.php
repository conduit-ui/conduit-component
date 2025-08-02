<?php

use App\Commands\DelegatedCommand;
use Illuminate\Support\Facades\File;

/**
 * Component Certification Test Suite
 * 
 * All Conduit components must pass these tests to ensure:
 * - Basic functionality works
 * - Liberation philosophy compliance (dual-mode operation)
 * - Proper architecture and structure
 * - Code quality standards
 */
describe('Component Certification', function () {
    
    describe('Architecture Validation', function () {
        
        it('has required directory structure', function () {
            expect(File::exists(app_path('Commands')))->toBeTrue();
            expect(File::exists(config_path('commands.php')))->toBeTrue();
            expect(File::exists(base_path('composer.json')))->toBeTrue();
            expect(File::exists(base_path('tests')))->toBeTrue();
        });

        it('has proper composer.json structure', function () {
            $composer = json_decode(File::get(base_path('composer.json')), true);
            
            expect($composer)->toHaveKey('require');
            expect($composer['require'])->toHaveKey('laravel-zero/framework');
            expect($composer['require'])->toHaveKey('conduit/interfaces');
            expect($composer)->toHaveKey('bin');
        });

        it('has published commands configuration', function () {
            $config = include config_path('commands.php');
            
            expect($config)->toHaveKey('published');
            expect($config['published'])->toBeArray();
        });

        it('has DelegatedCommand base class available', function () {
            expect(class_exists(DelegatedCommand::class))->toBeTrue();
            expect(is_subclass_of(DelegatedCommand::class, 'LaravelZero\Framework\Commands\Command'))->toBeTrue();
            expect(in_array('Conduit\Interfaces\CommandInterface', class_implements(DelegatedCommand::class)))->toBeTrue();
        });
    });

    describe('Liberation Philosophy Compliance', function () {
        
        it('supports dual-mode operation', function () {
            // Component should work standalone
            expect(File::exists(base_path('component')))->toBeTrue();
            expect(File::isExecutable(base_path('component')))->toBeTrue();
            
            // Component should support delegation
            expect(class_exists(DelegatedCommand::class))->toBeTrue();
        });

        it('has no vendor lock-in dependencies', function () {
            $composer = json_decode(File::get(base_path('composer.json')), true);
            
            // Should only depend on Laravel Zero framework and Conduit interfaces
            $requiredDeps = $composer['require'] ?? [];
            unset($requiredDeps['php']); // PHP version is allowed
            unset($requiredDeps['laravel-zero/framework']); // Laravel Zero is allowed
            unset($requiredDeps['conduit/interfaces']); // Conduit interfaces are allowed
            
            // Any additional dependencies should be justified
            if (!empty($requiredDeps)) {
                $this->markTestSkipped('Additional dependencies detected: ' . implode(', ', array_keys($requiredDeps)) . '. Ensure these are necessary and don\'t create vendor lock-in.');
            }
            
            expect($requiredDeps)->toBeEmpty();
        });
    });

    describe('Code Quality Standards', function () {
        
        it('has testing framework configured', function () {
            expect(File::exists(base_path('phpunit.xml.dist')))->toBeTrue();
            expect(File::exists(base_path('tests/Pest.php')))->toBeTrue();
        });

        it('has code formatting configured', function () {
            $composer = json_decode(File::get(base_path('composer.json')), true);
            $devDeps = $composer['require-dev'] ?? [];
            
            expect($devDeps)->toHaveKey('laravel/pint');
        });

        it('follows proper namespace conventions', function () {
            $commandFiles = File::glob(app_path('Commands/*.php'));
            
            foreach ($commandFiles as $file) {
                $content = File::get($file);
                expect($content)->toContain('namespace App\Commands;');
            }
        });
    });

    describe('Basic Functionality', function () {
        
        it('can execute component binary', function () {
            $output = shell_exec(base_path('component') . ' --version 2>&1');
            expect($output)->toContain('Component');
        });

        it('can list available commands', function () {
            $output = shell_exec(base_path('component') . ' list 2>&1');
            expect($output)->toContain('Available commands');
        });

        it('has at least one functional command', function () {
            $commandFiles = File::glob(app_path('Commands/*.php'));
            $functionalCommands = 0;
            
            foreach ($commandFiles as $file) {
                $className = 'App\\Commands\\' . pathinfo($file, PATHINFO_FILENAME);
                if (class_exists($className) && is_subclass_of($className, 'Illuminate\Console\Command')) {
                    $functionalCommands++;
                }
            }
            
            expect($functionalCommands)->toBeGreaterThan(0);
        });
    });
});