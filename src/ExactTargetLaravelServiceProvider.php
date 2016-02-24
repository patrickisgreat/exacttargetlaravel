<?php

namespace digitaladditive\ExactTargetLaravel;

use Illuminate\Support\ServiceProvider;

class ExactTargetLaravelServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'ExactTargetLaravelConfig.php' => config_path('ExactTargetLaravelConfig.php'),
        ]);
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}