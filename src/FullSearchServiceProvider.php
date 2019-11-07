<?php

namespace EmilMoe\FullSearch;

use Illuminate\Support\ServiceProvider;

class FullSearchServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->loadRoutesFrom(__DIR__ .'/routes/web.php');
        $this->mergeConfigFrom(__DIR__ .'/config.php', 'full-search');
        $this->publishes([__DIR__. '/config.php' => config_path('full-search.php')]);
    }
}