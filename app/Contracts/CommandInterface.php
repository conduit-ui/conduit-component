<?php

namespace App\Contracts;

use Illuminate\Console\Command;

interface CommandInterface
{
    /**
     * Capture knowledge metadata for the command.
     *
     * @return array Knowledge metadata about the command's purpose and execution
     */
    public function captureKnowledge(): array;

    /**
     * Determine if the command should be published to Conduit.
     *
     * @return bool Whether the command is publishable
     */
    public function isPublishable(): bool;

    /**
     * Get the liberation metrics for this command.
     *
     * @return array Metrics demonstrating developer efficiency gains
     */
    public function getLiberationMetrics(): array;
}