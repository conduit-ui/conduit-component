<?php

namespace App\Commands;

/**
 * Example command demonstrating modern Conduit component architecture
 * 
 * This command showcases:
 * - Universal output formats (terminal, json, table)
 * - Liberation philosophy with metrics
 * - Developer-friendly experience
 */
class ExampleCommand extends BaseCommand
{
    protected $signature = 'example {--format=terminal} {--output=}';
    protected $description = 'Example command showcasing universal output formats and liberation metrics';
    
    /**
     * Get the data to be formatted
     */
    public function getData(): array
    {
        return [
            [
                'name' => 'Universal Formats',
                'status' => 'active',
                'liberation_score' => '95%',
                'description' => 'JSON, table, and terminal output support'
            ],
            [
                'name' => 'Smart Detection',
                'status' => 'active', 
                'liberation_score' => '90%',
                'description' => 'Auto-detects piped output for jq compatibility'
            ],
            [
                'name' => 'File Export',
                'status' => 'active',
                'liberation_score' => '85%',
                'description' => 'Export to files with --output option'
            ],
            [
                'name' => 'Developer Experience',
                'status' => 'launching',
                'liberation_score' => '∞',
                'description' => 'Eliminates CLI workflow pain forever'
            ]
        ];
    }
    
    /**
     * Beautiful terminal output with emojis and liberation messaging
     */
    public function outputTerminal(array $data): int
    {
        $this->info('🚀 Modern Conduit Component Architecture');
        $this->line('');
        
        foreach ($data as $feature) {
            $status = match($feature['status']) {
                'active' => '✅',
                'launching' => '🚀',
                default => '❓'
            };
            
            $this->line("  {$status} <comment>{$feature['name']}</comment> ({$feature['liberation_score']})");
            $this->line("     {$feature['description']}");
            $this->line('');
        }
        
        $this->info('💡 Liberation Features:');
        $this->line('   --format=json    (automation & jq ready)');
        $this->line('   --format=table   (data analysis ready)');
        $this->line('   --output=file    (export ready)');
        $this->line('   | jq             (pipe detection!)');
        
        return 0;
    }
    
    /**
     * Enhanced liberation metrics for example command
     */
    public function getLiberationMetrics(): array
    {
        return [
            'complexity_reduction' => 0.8,  // 80% complexity reduction
            'time_saved_per_execution' => 2.5,  // 2.5 seconds saved per execution
        ];
    }
    
    /**
     * Knowledge capture for example command
     */
    public function captureKnowledge(): array
    {
        return array_merge(parent::captureKnowledge(), [
            'tags' => ['command', 'liberation', 'example', 'universal-formats'],
            'features_demonstrated' => [
                'universal_output_formats',
                'piped_detection',
                'file_export',
                'liberation_metrics',
                'knowledge_capture'
            ],
        ]);
    }
}