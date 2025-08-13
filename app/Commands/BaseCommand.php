<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

/**
 * BaseCommand for component commands
 * 
 * This provides all components with:
 * - Consistent CLI experience
 * - Laravel Zero command features
 */
abstract class BaseCommand extends Command
{
    /**
     * Get liberation metrics for this command
     * Override in concrete commands to provide specific metrics
     */
    public function getLiberationMetrics(): array
    {
        return [
            'complexity_reduction' => 0.5,
            'time_saved_per_execution' => 0.25,
        ];
    }
    
    /**
     * Capture knowledge about command execution
     * Override in concrete commands to provide specific knowledge
     */
    public function captureKnowledge(): array
    {
        return [
            'command' => $this->getName(),
            'description' => $this->getDescription(),
            'tags' => ['command', 'liberation'],
            'timestamp' => date('c'),
        ];
    }
    
    /**
     * Check if command should be published to component ecosystem
     */
    public function isPublishable(): bool
    {
        return true;
    }
}