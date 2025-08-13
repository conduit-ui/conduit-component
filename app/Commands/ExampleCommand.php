<?php

namespace App\Commands;

/**
 * Example command demonstrating component architecture
 */
class ExampleCommand extends BaseCommand
{
    protected $signature = 'example';

    protected $description = 'Example command showcasing the component';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $this->info('🚀 Component Example Command');
        $this->line('');
        $this->line('This is an example command showing how components work.');
        $this->line('');
        $this->info('Features:');
        $this->line('  ✅ Laravel Zero command structure');
        $this->line('  ✅ Extends BaseCommand for shared functionality');
        $this->line('  ✅ Ready for your custom logic');
        
        return self::SUCCESS;
    }
}