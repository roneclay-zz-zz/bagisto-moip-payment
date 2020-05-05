<?php

/**
 * Copyright (c) 2020. Rone Clay Brasil. All rights reserved.
 * @author    Rone Clay Brasil <roneclay@gmail.com>
 */

namespace Fineweb\Wirecard\Listeners\Checkout;

class Onepage
{
    /**
     * @param $payment
     */
    public function showPaymentForm($payment): void
    {
        try {
            $params = json_decode(json_encode($payment->getParams(), true), true);
            $paymentMethod = $params['payment']['method'];

            if ('wirecardcc' === $paymentMethod) {
                $payment->addTemplate('wirecard::checkout.onepage.cc.form');
            }
        } catch (\Exception $e) {
            report($e);
        }
    }
}