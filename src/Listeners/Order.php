<?php
/**
 * Copyright (c) 2020. Rone Clay Brasil. All rights reserved.
 * @author    Rone Clay Brasil <roneclay@gmail.com>
 */

namespace Fineweb\Wirecard\Listeners;

class Order
{
    /**
     * @param $order
     */
    public function showPaymentInfo($order): void
    {
        try {
            $params = json_decode(json_encode($order->getParams(), true), true);
            $paymentMethod = $params['order']['payment']['method'];

            if ('wirecardboleto' === $paymentMethod) {
                $order->addTemplate('wirecard::sales.order.payment.boleto');
            }
        } catch (\Exception $e) {
            report($e);
        }
    }
}