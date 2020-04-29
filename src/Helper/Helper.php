<?php
/**
 * Copyright (c) 2020. Rone Clay Brasil. All rights reserved.
 * @author    Rone Clay Brasil <roneclay@gmail.com>
 */

namespace Fineweb\Wirecard\Helper;

use Illuminate\Support\Facades\DB;

class Helper
{
    /**
     * @param $document
     * @return string|string[]
     */
    public function documentParser ($document)
    {
        return str_replace(str_split('-.'), '', $document);
    }

    /**
     * @param $phone
     * @param int $num
     * @return mixed
     */
    public function splitPhone ($phone, $num = 2) {
        $length = strlen($phone);
        $output[0] = substr($phone, 0, $num);
        $output[1] = substr($phone, $num, $length );
        return $output;
    }

    /**
     * @param $price
     * @return int
     */
    public function formatPrice($price)
    {
        $price = number_format((float) $price, 2);
        $price = (int) str_replace('.','', $price);

        return $price;
    }

    /**
     * @param $order
     * @return mixed
     */
    public function getBoletoLink($order)
    {
        $attributes = $order->getAttributes();
        $increment_id = $attributes['increment_id'];
        $paymentData = DB::table('moip_payments')->where('increment_id', $increment_id)->value('moip_payment_data');
        $payment = json_decode($paymentData, true);

        return $payment['_links']['payBoleto']['redirectHref'];
    }
}