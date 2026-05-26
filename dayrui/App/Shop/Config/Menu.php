<?php

/**
 * Admin menu configuration for the custom shop app.
 */

return [
    'admin' => [
        'shop' => [
            'name' => '商城',
            'icon' => 'fa fa-shopping-cart',
            'displayorder' => '0',
            'left' => [
                'shop-manage' => [
                    'name' => '商城管理',
                    'icon' => 'fa fa-shopping-cart',
                    'link' => [
                        [
                            'name' => '订单管理',
                            'icon' => 'fa fa-list',
                            'uri' => 'shop/order/index',
                        ],
                        [
                            'name' => '会员资料',
                            'icon' => 'fa fa-user',
                            'uri' => 'shop/profile/index',
                        ],
                        [
                            'name' => '支付配置',
                            'icon' => 'fa fa-credit-card',
                            'uri' => 'shop/payconfig/index',
                        ],
                        [
                            'name' => '防伪码导入',
                            'icon' => 'fa fa-shield',
                            'uri' => 'shop/antifake/index',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
