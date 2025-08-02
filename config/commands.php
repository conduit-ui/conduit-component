<?php

use LaravelZero\Framework\Commands\BuildCommand;
use LaravelZero\Framework\Commands\InstallCommand;
use LaravelZero\Framework\Commands\MakeCommand;
use LaravelZero\Framework\Commands\RenameCommand;
use LaravelZero\Framework\Commands\TestMakeCommand;
use NunoMaduro\Collision\Adapters\Laravel\Commands\TestCommand;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Command
    |--------------------------------------------------------------------------
    |
    | Laravel Zero will always run the command specified below when no command name is
    | provided. Consider update the default command for single command applications.
    | You cannot pass arguments to the default command because they are ignored.
    |
    */

    "default" => NunoMaduro\LaravelConsoleSummary\SummaryCommand::class,

    /*
    |--------------------------------------------------------------------------
    | Commands Paths
    |--------------------------------------------------------------------------
    |
    | This value determines the "paths" that should be loaded by the console's
    | kernel. Foreach "path" present on the array provided below the kernel
    | will extract all "Illuminate\Console\Command" based class commands.
    |
    */

    "paths" => [
        app_path("Commands"),
        // Add additional command discovery paths
    ],

    /*
    |--------------------------------------------------------------------------
    | Added Commands
    |--------------------------------------------------------------------------
    |
    | You may want to include a single command class without having to load an
    | entire folder. Here you can specify which commands should be added to
    | your list of commands. The console's kernel will try to load them.
    |
    */

    "add" => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Hidden Commands
    |--------------------------------------------------------------------------
    |
    | Your application commands will always be visible on the application list
    | of commands. But you can still make them "hidden" specifying an array
    | of commands below. All "hidden" commands can still be run/executed.
    |
    */

    "hidden" => [
        NunoMaduro\LaravelConsoleSummary\SummaryCommand::class,
        Symfony\Component\Console\Command\DumpCompletionCommand::class,
        Symfony\Component\Console\Command\HelpCommand::class,
        Illuminate\Console\Scheduling\ScheduleRunCommand::class,
        Illuminate\Console\Scheduling\ScheduleListCommand::class,
        Illuminate\Console\Scheduling\ScheduleFinishCommand::class,
        Illuminate\Foundation\Console\VendorPublishCommand::class,
        LaravelZero\Framework\Commands\StubPublishCommand::class,
        LaravelZero\Framework\Commands\BuildCommand::class,
        LaravelZero\Framework\Commands\InstallCommand::class,
        LaravelZero\Framework\Commands\RenameCommand::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Published Commands (Conduit Delegation)
    |--------------------------------------------------------------------------
    |
    | Commands listed here will be available through Conduit delegation when
    | the delegation system is released. Commands extending DelegatedCommand
    | and marked as publishable will be automatically discovered and added here.
    | 
    | Liberation Philosophy: Components work standalone AND with Conduit
    |
    */
    "published" => [
        // Commands marked as publishable via DelegatedCommand::$publishable = true
        // Will be automatically discovered and registered for delegation
        // Example: 'component:example' => App\Commands\ExampleCommand::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Removed Commands
    |--------------------------------------------------------------------------
    |
    | Do you have a service provider that loads a list of commands that
    | you don't need? No problem. Laravel Zero allows you to specify
    | below a list of commands that you don't to see in your app.
    |
    */

    "remove" => [
        //
    ],
];
