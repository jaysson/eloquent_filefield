<?php

namespace Jaysson\EloquentFileField;

use Illuminate\Support\ServiceProvider;

class EloquentFileFieldProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/eloquent_filefield.php' => config_path('eloquent_filefield.php'),
        ]);
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/eloquent_filefield.php', 'eloquent_filefield');
    }
}