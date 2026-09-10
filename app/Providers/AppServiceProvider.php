<?php

namespace App\Providers;

use App\Console\PreventFrozenMigrations;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            Event::listen(CommandStarting::class, PreventFrozenMigrations::class);
        }

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
