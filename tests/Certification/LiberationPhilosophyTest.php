<?php

use Tests\TestCase;
use App\Contracts\CommandInterface;
use Illuminate\Support\Facades\File;

describe('Liberation Philosophy Certification', function () {
    it('validates all commands support dual-mode operation', function () {
        // Test that commands can run both standalone and through conduit
        $this->artisan('list')
            ->assertExitCode(0);
            
        // Verify the component can be called directly
        $directResult = shell_exec('./component list 2>&1');
        expect($directResult)->not()->toBeNull();
        expect($directResult)->not()->toContain('Fatal error');
    });

    it('ensures commands provide liberation metrics', function () {
        $commandPath = app_path('Commands');
        
        if (!File::exists($commandPath)) {
            $this->markTestSkipped('No commands directory found');
        }

        $commandFiles = File::glob($commandPath . '/*.php');
        
        foreach ($commandFiles as $file) {
            $className = 'App\\Commands\\' . pathinfo($file, PATHINFO_FILENAME);
            
            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                
                if ($reflection->implementsInterface(CommandInterface::class) && !$reflection->isAbstract()) {
                    $command = $reflection->newInstance();
                    $metrics = $command->getLiberationMetrics();
                    
                    expect($metrics)->toBeArray("Liberation metrics should be array for {$className}");
                    
                    // Check for key liberation metrics
                    expect($metrics)->toHaveKey('complexity_reduction', 
                        "Command {$className} should report complexity reduction metric");
                    expect($metrics)->toHaveKey('time_saved_per_execution', 
                        "Command {$className} should report time savings metric");
                        
                    // Validate metric values are reasonable
                    if (isset($metrics['complexity_reduction'])) {
                        expect($metrics['complexity_reduction'])->toBeNumeric(
                            "Complexity reduction should be numeric for {$className}");
                        expect($metrics['complexity_reduction'])->toBeGreaterThanOrEqual(0,
                            "Complexity reduction should be non-negative for {$className}");
                    }
                    
                    if (isset($metrics['time_saved_per_execution'])) {
                        expect($metrics['time_saved_per_execution'])->toBeNumeric(
                            "Time saved should be numeric for {$className}");
                        expect($metrics['time_saved_per_execution'])->toBeGreaterThanOrEqual(0,
                            "Time saved should be non-negative for {$className}");
                    }
                }
            }
        }
    });

    it('validates commands capture meaningful knowledge', function () {
        $commandPath = app_path('Commands');
        
        if (!File::exists($commandPath)) {
            $this->markTestSkipped('No commands directory found');
        }

        $commandFiles = File::glob($commandPath . '/*.php');
        
        foreach ($commandFiles as $file) {
            $className = 'App\\Commands\\' . pathinfo($file, PATHINFO_FILENAME);
            
            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                
                if ($reflection->implementsInterface(CommandInterface::class) && !$reflection->isAbstract()) {
                    $command = $reflection->newInstance();
                    $knowledge = $command->captureKnowledge();
                    
                    expect($knowledge)->toBeArray("Knowledge should be array for {$className}");
                    
                    // Validate required knowledge fields
                    expect($knowledge)->toHaveKey('command', "Knowledge should include command name for {$className}");
                    expect($knowledge)->toHaveKey('description', "Knowledge should include description for {$className}");
                    expect($knowledge)->toHaveKey('tags', "Knowledge should include tags for {$className}");
                    
                    // Validate knowledge content quality
                    expect($knowledge['command'])->not()->toBeEmpty("Command name should not be empty for {$className}");
                    expect($knowledge['description'])->not()->toBeEmpty("Description should not be empty for {$className}");
                    expect($knowledge['tags'])->toBeArray("Tags should be array for {$className}");
                    expect($knowledge['tags'])->not()->toBeEmpty("Tags should not be empty for {$className}");
                }
            }
        }
    });

    it('ensures commands demonstrate developer empowerment', function () {
        // Test that commands provide clear help and usage information
        $this->artisan('list')
            ->expectsOutput(function ($output) {
                // Should show available commands with descriptions
                return str_contains($output, 'Available commands:') || 
                       str_contains($output, 'USAGE:');
            })
            ->assertExitCode(0);
    });

    it('validates publishability logic is meaningful', function () {
        $commandPath = app_path('Commands');
        
        if (!File::exists($commandPath)) {
            $this->markTestSkipped('No commands directory found');
        }

        $commandFiles = File::glob($commandPath . '/*.php');
        $publishableCount = 0;
        $totalCommands = 0;
        
        foreach ($commandFiles as $file) {
            $className = 'App\\Commands\\' . pathinfo($file, PATHINFO_FILENAME);
            
            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                
                if ($reflection->implementsInterface(CommandInterface::class) && !$reflection->isAbstract()) {
                    $totalCommands++;
                    $command = $reflection->newInstance();
                    
                    if ($command->isPublishable()) {
                        $publishableCount++;
                    }
                }
            }
        }
        
        // At least some commands should be publishable if any exist
        if ($totalCommands > 0) {
            expect($publishableCount)->toBeGreaterThan(0, 
                'At least some commands should be publishable to demonstrate liberation philosophy');
        }
    });

    it('ensures component promotes automation and efficiency', function () {
        // Check that the component has automation-focused commands
        $result = $this->artisan('list');
        $output = $result->getDisplay();
        
        // Look for common automation patterns
        $automationIndicators = [
            'make:', // Code generation
            'test',  // Automated testing
            'build', // Automated building  
            'deploy', // Automated deployment
            'generate', // Code generation
            'create', // Resource creation
        ];
        
        $foundAutomation = false;
        foreach ($automationIndicators as $indicator) {
            if (str_contains(strtolower($output), $indicator)) {
                $foundAutomation = true;
                break;
            }
        }
        
        // Component should demonstrate some form of automation
        expect($foundAutomation)->toBeTrue(
            'Component should include automation-focused commands to align with liberation philosophy');
    });

    it('validates component enhances developer workflow', function () {
        // Test that the component binary is easily accessible
        $componentPath = base_path('component');
        expect(file_exists($componentPath))->toBeTrue('Component should have an accessible binary');
        expect(is_executable($componentPath))->toBeTrue('Component binary should be executable');
        
        // Test that it provides helpful output
        $helpOutput = shell_exec('./component --help 2>&1');
        expect($helpOutput)->not()->toBeNull();
        expect($helpOutput)->not()->toBeEmpty();
        expect(strlen($helpOutput))->toBeGreaterThan(50, 'Help output should be substantial and helpful');
    });
});