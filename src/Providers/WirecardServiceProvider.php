<?php
/**
 * Copyright (c) 2020. Rone Clay Brasil. All rights reserved.
 * @author    Rone Clay Brasil <roneclay@gmail.com>
 */

namespace Fineweb\Wirecard\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

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
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->loadJSONTranslationsFrom(__DIR__ . '/../Resources/lang');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'wirecard');

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/system.php', 'core'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/paymentmethods.php', 'paymentmethods'
        );

        Event::listen(
            'bagisto.shop.customers.account.orders.view.payment-method.after',
            'Fineweb\Wirecard\Listeners\Order@showPaymentInfo'
        );

        Event::listen(
            'sales.order.payment-method.after',
            'Fineweb\Wirecard\Listeners\Order@showPaymentInfo'
        );
    }
}
