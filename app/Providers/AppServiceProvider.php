<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register git version service
        $this->app->singleton('git.version', function () {
            // Try to get git version, fallback to default if not available
            $gitVersion = shell_exec('git describe --tags --always 2>/dev/null');
            return $gitVersion ? trim($gitVersion) : 'v1.0.0';
        });
    }
}
