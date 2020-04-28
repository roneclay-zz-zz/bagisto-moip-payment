<?php
/**
 * Copyright (c) 2020. Rone Clay Brasil. All rights reserved.
 * @author    Rone Clay Brasil <roneclay@gmail.com>
 */

return [
    [
        'key' => 'sales.paymentmethods.wirecardconfig',
        'name' => 'Wirecard (Moip) - Settings',
        'sort' => 100,
        'fields' => [
            [
                'name' => 'sandbox',
                'title' => 'admin::app.admin.system.sandbox',
                'type' => 'boolean',
                'validation' => 'required',
                'channel_based' => false,
                'locale_based' => true,
            ],
            [
                'name' => 'token',
                'title' => 'Token',
                'type' => 'text',
                'validation' => 'required',
                'info' => __('In your wirecard account, got to: My account > Settings > Access key'),
                'locale_based' => true,
                'channel_based' => false
            ],
            [
                'name' => 'access_key',
                'title' => 'Access key',
                'type' => 'text',
                'validation' => 'required',
                'locale_based' => true,
                'channel_based' => false
            ]
        ]
    ],
    [
        'key' => 'sales.paymentmethods.wirecardboleto',
        'name' => 'Wirecard (Moip) - Boleto',
        'sort' => 100,
        'fields' => [
            [
                'name' => 'title',
                'title' => __('Title'),
                'type' => 'text',
                'validation' => 'required',
                'channel_based' => false,
                'locale_based' => true
            ],
            [
                'name' => 'active',
                'title' => 'admin::app.admin.system.status',
                'type' => 'select',
                'options' => [
                    [
                        'title' => __('Active'),
                        'value' => true
                    ], [
                        'title' => __('Inactive'),
                        'value' => false
                    ]
                ],
                'validation' => 'required'
            ],
            [
                'name' => 'instructions',
                'title' => __('Instructions'),
                'type' => 'text',
                'validation' => 'required',
                'channel_based' => false,
                'locale_based' => true,
            ],
        ]
    ]
];