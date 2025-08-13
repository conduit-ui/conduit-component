<?php

namespace Tests\Unit\Commands;

use Tests\TestCase;
use App\Commands\BaseCommand;
use LaravelZero\Framework\Commands\Command;

class BaseCommandTest extends TestCase
{
    /**
     * Test that BaseCommand is abstract
     */
    public function test_base_command_is_abstract(): void
    {
        $reflection = new \ReflectionClass(BaseCommand::class);
        $this->assertTrue($reflection->isAbstract());
    }

    /**
     * Test that BaseCommand extends correct parent
     */
    public function test_base_command_extends_laravel_command(): void
    {
        $reflection = new \ReflectionClass(BaseCommand::class);
        $this->assertTrue($reflection->isSubclassOf(Command::class));
    }

    /**
     * Test that BaseCommand doesn't have interface dependencies
     */
    public function test_base_command_has_no_external_interfaces(): void
    {
        $reflection = new \ReflectionClass(BaseCommand::class);
        $constructor = $reflection->getConstructor();
        
        // Constructor should exist (inherited from Command)
        $this->assertNotNull($constructor);
        
        // Check that no methods require ConduitInterfaces
        foreach ($reflection->getMethods() as $method) {
            $docComment = $method->getDocComment();
            if ($docComment) {
                $this->assertStringNotContains('ConduitInterfaces', $docComment);
                $this->assertStringNotContains('AbstractConduitCommand', $docComment);
            }
        }
    }
}