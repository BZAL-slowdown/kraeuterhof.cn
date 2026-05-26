<?php

return [
    'wechat_h5' => [
        'enabled' => false,
        'appid' => '',
        'mchid' => '',
        'merchant_serial_no' => '',
        'merchant_private_key' => WRITEPATH.'pay/wechat/apiclient_key.pem',
        'platform_public_key' => WRITEPATH.'pay/wechat/wechatpay_public.pem',
        'api_v3_key' => '',
        'notify_url' => SITE_URL.'wechatpay_notify.php',
        'return_url' => SITE_URL.'index.php?s=shop&c=pay&m=return_page&out_trade_no={out_trade_no}',
        'scene_info' => [
            'type' => 'Wap',
            'wap_url' => SITE_URL,
            'wap_name' => SITE_NAME,
        ],
    ],
    'alipay_wap' => [
        'enabled' => false,
        'account_id' => '',
        'seller_id' => '',
        'app_id' => '',
        'app_private_key' => WRITEPATH.'pay/alipay/app_private_key.pem',
        'merchant_private_key' => WRITEPATH.'pay/alipay/app_private_key.pem',
        'app_public_key' => WRITEPATH.'pay/alipay/app_public_key.pem',
        'alipay_public_key' => WRITEPATH.'pay/alipay/alipay_public_key.pem',
        'notify_url' => SITE_URL.'alipay_notify.php',
        'return_url' => SITE_URL.'index.php?s=shop&c=pay&m=alipay_return&out_trade_no={out_trade_no}',
        'gateway' => 'https://openapi.alipay.com/gateway.do',
        'product_code' => 'QUICK_WAP_WAY',
        'sign_type' => 'RSA2',
        'charset' => 'utf-8',
        'quit_url' => SITE_URL,
    ],
];
