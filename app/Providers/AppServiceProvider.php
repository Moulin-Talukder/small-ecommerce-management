<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        // N+1 guard: lazy loading a relation on a model that was fetched in a
        // collection throws in tests and is logged locally; off in production.
        Model::preventLazyLoading(! $this->app->isProduction());

        if (! $this->app->runningUnitTests()) {
            Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) {
                Log::warning('N+1 lazy loading: '.$model::class."::{$relation}");
            });
        }
    }
}
