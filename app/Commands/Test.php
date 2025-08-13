<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use NunoMaduro\Collision\Adapters\Laravel\Commands\TestCommand;

class Test extends TestCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the component tests';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        parent::handle();
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
