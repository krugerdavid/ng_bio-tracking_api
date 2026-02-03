<?php

namespace App\Providers;

use App\Observers\AuditObserver;
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
        $this->registerAuditObservers();
    }

    private function registerAuditObservers(): void
    {
        foreach (config('audit.models', []) as $model) {
            if (is_string($model) && class_exists($model)) {
                $model::observe(AuditObserver::class);
            }
        }
    }
}
