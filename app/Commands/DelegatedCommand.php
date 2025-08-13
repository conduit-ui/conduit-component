<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

/**
 * Base class for commands that support delegation
 *
 * This enables dual-mode operation:
 * - Standalone: Works independently as Laravel Zero command
 * - Delegated: Integrates with ecosystem via delegation
 */
abstract class DelegatedCommand extends Command
{
    /**
     * Check if running in delegation context
     */
    protected function isDelegated(): bool
    {
        return getenv('CONDUIT_DELEGATION') === 'true';
    }
}
