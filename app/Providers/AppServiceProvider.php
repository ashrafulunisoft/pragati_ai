<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Visit;
use App\Observers\VisitObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register MCP service
        $this->app->singleton('mcp', function ($app) {
            return new \App\Services\MCPService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Visit::observe(VisitObserver::class);
    }

    /**
     * The event listener mappings for the application.
     *
     * @return array<class-string, array<int, class-string>>
     */
    protected $listen = [
        \App\Events\McpAttackDetected::class => [
            \App\Listeners\SendSecurityAlertEmail::class,
        ],
    ];
}
