<?php

namespace App\Providers;

use App\Domain\TreeReward;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The domain rule is built once from config; this is the only place
        // that reads VISITS_PER_TREE, keeping TreeReward framework-free.
        $this->app->singleton(TreeReward::class, fn () => new TreeReward(
            (int) config('trees.visits_per_tree')
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
