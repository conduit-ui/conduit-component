<?php

namespace Tests\Feature;

use Tests\TestCase;

class SkeletonTest extends TestCase
{
    /**
     * Test that the component executable runs without errors
     */
    public function test_component_runs_without_errors(): void
    {
        $this->artisan('list')
            ->assertExitCode(0);
    }

    /**
     * Test that the example command exists
     */
    public function test_example_command_exists(): void
    {
        $this->artisan('list')
            ->assertExitCode(0)
            ->expectsOutputToContain('example');
    }

    /**
     * Test that the App namespace is properly configured
     */
    public function test_app_namespace_is_configured(): void
    {
        $this->assertTrue(class_exists(\App\Commands\BaseCommand::class));
        $this->assertTrue(class_exists(\App\Commands\ExampleCommand::class));
    }

    /**
     * Test that Laravel Zero commands are available
     */
    public function test_laravel_zero_commands_available(): void
    {
        $this->artisan('list')
            ->assertExitCode(0)
            ->expectsOutputToContain('make:command')
            ->expectsOutputToContain('make:test');
    }

    /**
     * Test that the manifest file has correct structure
     */
    public function test_manifest_structure_is_valid(): void
    {
        $manifestPath = base_path('💩.json');
        $this->assertFileExists($manifestPath);
        
        $manifest = json_decode(file_get_contents($manifestPath), true);
        
        $this->assertIsArray($manifest);
        $this->assertArrayHasKey('name', $manifest);
        $this->assertArrayHasKey('description', $manifest);
        $this->assertArrayHasKey('version', $manifest);
        $this->assertArrayHasKey('commands', $manifest);
        $this->assertArrayNotHasKey('executable', $manifest); // Should not have executable field
    }

    /**
     * Test composer.json uses App namespace
     */
    public function test_composer_json_uses_app_namespace(): void
    {
        $composerPath = base_path('composer.json');
        $this->assertFileExists($composerPath);
        
        $composer = json_decode(file_get_contents($composerPath), true);
        
        $this->assertArrayHasKey('autoload', $composer);
        $this->assertArrayHasKey('psr-4', $composer['autoload']);
        $this->assertArrayHasKey('App\\', $composer['autoload']['psr-4']);
        $this->assertEquals('app/', $composer['autoload']['psr-4']['App\\']);
    }

    /**
     * Test that BaseCommand extends Laravel Command
     */
    public function test_base_command_extends_laravel_command(): void
    {
        $baseCommand = new \ReflectionClass(\App\Commands\BaseCommand::class);
        $this->assertTrue($baseCommand->isSubclassOf(\LaravelZero\Framework\Commands\Command::class));
    }

    /**
     * Test that DelegatedCommand doesn't implement non-existent interfaces
     */
    public function test_delegated_command_has_no_interface_dependencies(): void
    {
        $delegatedCommand = new \ReflectionClass(\App\Commands\DelegatedCommand::class);
        $interfaces = $delegatedCommand->getInterfaces();
        
        // Should not implement any interfaces (besides those inherited from parent)
        foreach ($interfaces as $interface) {
            $this->assertStringNotContainsString('ConduitInterfaces', $interface->getName());
            $this->assertStringNotContainsString('CommandInterface', $interface->getName());
        }
    }
}