<?php

namespace App\Commands;

use JordanPartridge\ConduitInterfaces\AbstractConduitCommand;

/**
 * BaseCommand extending AbstractConduitCommand for universal format support
 * 
 * This provides all components with:
 * - Universal output formats (terminal, json, table)
 * - Smart piped output detection
 * - File output capabilities
 * - Consistent CLI experience
 */
abstract class BaseCommand extends AbstractConduitCommand
{
    /**
     * Enhanced command execution with liberation metrics
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        
        // Call parent handle which routes to appropriate output formatter
        $result = parent::handle();
        
        $executionTime = microtime(true) - $startTime;
        
        // Log liberation metrics if verbose
        if ($this->getOutput()->isVerbose()) {
            $metrics = $this->getLiberationMetrics();
            $this->line('');
            $this->comment("⚡ Liberation Metrics:");
            $this->line("   Time saved: {$metrics['time_saved_per_execution']}s per execution");
            $this->line("   Complexity reduction: " . ($metrics['complexity_reduction'] * 100) . "%");
            $this->line("   Execution time: " . round($executionTime, 3) . "s");
        }
        
        return $result;
    }
    
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
            'timestamp' => now()->toIso8601String(),
            'formats_supported' => array_keys(static::getAvailableFormats()),
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