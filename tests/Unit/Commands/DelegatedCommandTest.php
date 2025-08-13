<?php

namespace Tests\Unit\Commands;

use App\Commands\DelegatedCommand;
use LaravelZero\Framework\Commands\Command;
use Tests\TestCase;

class DelegatedCommandTest extends TestCase
{
    /**
     * Test that DelegatedCommand is abstract
     */
    public function test_delegated_command_is_abstract(): void
    {
        $reflection = new \ReflectionClass(DelegatedCommand::class);
        $this->assertTrue($reflection->isAbstract());
    }

    /**
     * Test that DelegatedCommand extends Command
     */
    public function test_delegated_command_extends_command(): void
    {
        $reflection = new \ReflectionClass(DelegatedCommand::class);
        $this->assertTrue($reflection->isSubclassOf(Command::class));
    }

    /**
     * Test that isDelegated method exists
     */
    public function test_is_delegated_method_exists(): void
    {
        $reflection = new \ReflectionClass(DelegatedCommand::class);
        $this->assertTrue($reflection->hasMethod('isDelegated'));

        $method = $reflection->getMethod('isDelegated');
        $this->assertTrue($method->isProtected());
        $this->assertEquals('bool', $method->getReturnType()->getName());
    }

    /**
     * Test that DelegatedCommand doesn't override execute incorrectly
     */
    public function test_delegated_command_doesnt_override_execute(): void
    {
        $reflection = new \ReflectionClass(DelegatedCommand::class);

        // The execute method should not be declared in DelegatedCommand
        // (it was causing signature mismatch errors)
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($method->getName() === 'execute' && $method->getDeclaringClass()->getName() === DelegatedCommand::class) {
                $this->fail('DelegatedCommand should not declare its own execute method');
            }
        }

        $this->assertTrue(true); // Test passed if we get here
    }

    /**
     * Test environment variable check for delegation
     */
    public function test_delegation_check_uses_environment(): void
    {
        // Create a concrete test implementation
        $command = new class extends DelegatedCommand
        {
            protected $signature = 'test:command';

            protected $description = 'Test command';

            public function handle()
            {
                return 0;
            }

            public function test_is_delegated(): bool
            {
                return $this->isDelegated();
            }
        };

        // Test without environment variable
        putenv('CONDUIT_DELEGATION=');
        $this->assertFalse($command->testIsDelegated());

        // Test with delegation enabled
        putenv('CONDUIT_DELEGATION=true');
        $this->assertTrue($command->testIsDelegated());

        // Clean up
        putenv('CONDUIT_DELEGATION=');
    }
}
