<?php

namespace App\Providers;

use App\Console\PreventFrozenMigrations;
use App\Services\ApprovalCenterService;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Satu instance per request menjaga perhitungan badge desktop/mobile
        // memakai cache antrean yang sama.
        $this->app->singleton(ApprovalCenterService::class);
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

        View::composer([
            'admin.components.sidebar',
            'admin.components.sidebar-mobile',
        ], function ($view): void {
            $user = auth()->user();
            $count = $user?->role === 'admin'
                ? app(ApprovalCenterService::class)->pendingCountForAdmin((int) $user->id_user)
                : 0;

            $view->with('adminApprovalInboxCount', $count);
        });
    }
}
