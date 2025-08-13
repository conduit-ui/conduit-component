<?php

namespace Tests\Integration;

use Tests\TestCase;
use Illuminate\Support\Facades\File;

class ComponentBootstrapTest extends TestCase
{
    /**
     * Test that the component bootstraps without errors
     */
    public function test_component_bootstraps_successfully(): void
    {
        // Component should already be bootstrapped in TestCase
        $this->assertTrue($this->app->isBooted());
    }

    /**
     * Test that app directory structure is correct
     */
    public function test_app_directory_structure_exists(): void
    {
        $this->assertDirectoryExists(base_path('app'));
        $this->assertDirectoryExists(base_path('app/Commands'));
        $this->assertDirectoryDoesNotExist(base_path('src')); // Should not have src directory
    }

    /**
     * Test that critical files exist
     */
    public function test_critical_files_exist(): void
    {
        $this->assertFileExists(base_path('composer.json'));
        $this->assertFileExists(base_path('💩.json'));
        $this->assertFileExists(base_path('component'));
        $this->assertFileIsExecutable(base_path('component'));
    }

    /**
     * Test that autoloading works correctly
     */
    public function test_autoloading_configured_correctly(): void
    {
        // Classes should be autoloadable
        $this->assertTrue(class_exists(\App\Commands\BaseCommand::class));
        $this->assertTrue(class_exists(\App\Commands\DelegatedCommand::class));
        $this->assertTrue(class_exists(\App\Commands\ExampleCommand::class));
        
        // Non-existent namespaces should not be loaded
        $this->assertFalse(class_exists(\ConduitComponents\Spotify\SpotifyService::class));
        $this->assertFalse(class_exists(\JordanPartridge\ConduitInterfaces\AbstractConduitCommand::class));
    }

    /**
     * Test that the component can be executed via CLI
     */
    public function test_component_cli_execution(): void
    {
        $output = shell_exec('php ' . base_path('component') . ' list 2>&1');
        
        $this->assertNotNull($output);
        $this->assertStringContainsString('Component', $output);
        $this->assertStringContainsString('example', $output);
        $this->assertStringNotContainsString('Error', $output);
        $this->assertStringNotContainsString('Exception', $output);
    }

    /**
     * Test that templates use placeholders correctly
     */
    public function test_templates_have_placeholders(): void
    {
        // Revert manifest to template version for testing
        $manifestContent = File::get(base_path('💩.json'));
        
        // For testing, we check that our test manifest doesn't have executable field
        $manifest = json_decode($manifestContent, true);
        $this->assertArrayNotHasKey('executable', $manifest);
        
        // Check that the manifest uses placeholder pattern
        $this->assertStringContainsString('{{', $manifestContent);
        $this->assertStringContainsString('}}', $manifestContent);
    }

    /**
     * Test composer.json validation
     */
    public function test_composer_json_is_valid(): void
    {
        $composerPath = base_path('composer.json');
        $composer = json_decode(file_get_contents($composerPath), true);
        
        // Check required fields
        $this->assertArrayHasKey('name', $composer);
        $this->assertArrayHasKey('require', $composer);
        $this->assertArrayHasKey('autoload', $composer);
        
        // Check PHP version requirement
        $this->assertArrayHasKey('php', $composer['require']);
        $this->assertStringContainsString('8.2', $composer['require']['php']);
        
        // Check Laravel Zero requirement
        $this->assertArrayHasKey('laravel-zero/framework', $composer['require']);
    }
}