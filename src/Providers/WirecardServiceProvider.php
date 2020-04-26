<?php

namespace Fineweb\Wirecard\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Class WirecardServiceProvider
 * @package Fineweb\Wirecard\Providers
 */
class  WirecardServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__ . '/../Http/routes.php';

        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->loadJSONTranslationsFrom(__DIR__ . '/../Resources/lang');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'wirecard');

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/system.php', 'core'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/paymentmethods.php', 'paymentmethods'
        );
    }
}
