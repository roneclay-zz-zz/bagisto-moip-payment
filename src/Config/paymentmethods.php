<?php
/**
 * Copyright (c) 2020. Rone Clay Brasil. All rights reserved.
 * @author    Rone Clay Brasil <roneclay@gmail.com>
 */

return [
    'wirecardboleto'  => [
        'code'              => 'wirecardboleto',
        'title'             => 'Wirecard (Moip) - Boleto',
        'class'             => \Fineweb\Wirecard\Payment\Wirecard::class,
        'active'            => true,
        'sort'              => 100,
        'description'       => __('Boleto usually takes up to 72 business hours to be cleared, that is, 3 business days.')
    ],
];