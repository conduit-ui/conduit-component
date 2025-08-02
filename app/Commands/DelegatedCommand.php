<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;
use Conduit\Interfaces\CommandInterface;

/**
 * Base class for commands that support Conduit delegation
 * 
 * This enables dual-mode operation:
 * - Standalone: Works independently as Laravel Zero command
 * - Delegated: Integrates with Conduit ecosystem via delegation
 */
abstract class DelegatedCommand extends Command implements CommandInterface
{
    /**
     * Indicates if this command should be published to Conduit
     */
    protected bool $publishable = true;

    /**
     * Command category for organization in Conduit
     */
    protected string $category = 'general';

    /**
     * Execute the command in dual-mode compatible way
     */
    public function handle(): int
    {
        // Liberation philosophy: Works the same standalone or delegated
        return $this->execute();
    }

    /**
     * Abstract method that child commands must implement
     */
    abstract protected function execute(): int;

    /**
     * Check if running in Conduit delegation context
     */
    protected function isDelegated(): bool
    {
        return isset($_ENV['CONDUIT_DELEGATION']) || 
               isset($_SERVER['CONDUIT_DELEGATION']) ||
               defined('CONDUIT_DELEGATION');
    }

    /**
     * Get command category for Conduit organization
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * Check if command should be published to Conduit
     */
    public function isPublishable(): bool
    {
        return $this->publishable;
    }
}