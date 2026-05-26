<?php namespace Phpcmf\Controllers\Admin;

class Payconfig extends \Phpcmf\App
{
    public function index()
    {
        $pay = is_file(APPPATH.'Config/Pay.php') ? require APPPATH.'Config/Pay.php' : [];
        $wechat = $pay['wechat_h5'] ?? [];
        $alipay = $pay['alipay_wap'] ?? [];
        $alipayPrivateKey = $alipay['app_private_key'] ?? ($alipay['merchant_private_key'] ?? '');

        \Phpcmf\Service::V()->assign([
            'wechat' => $wechat,
            'alipay' => $alipay,
            'private_key_exists' => !empty($wechat['merchant_private_key']) && is_file($wechat['merchant_private_key']),
            'public_key_exists' => !empty($wechat['platform_public_key']) && is_file($wechat['platform_public_key']),
            'alipay_private_key_exists' => $alipayPrivateKey && is_file($alipayPrivateKey),
            'alipay_app_public_key_exists' => !empty($alipay['app_public_key']) && is_file($alipay['app_public_key']),
            'alipay_public_key_exists' => !empty($alipay['alipay_public_key']) && is_file($alipay['alipay_public_key']),
        ]);
        \Phpcmf\Service::V()->display('payconfig_index.html');
    }

    public function save()
    {
        if (!IS_POST) {
            $this->_json(0, '非法请求');
        }

        $old = is_file(APPPATH.'Config/Pay.php') ? require APPPATH.'Config/Pay.php' : [];
        $oldWechat = $old['wechat_h5'] ?? [];
        $oldAlipay = $old['alipay_wap'] ?? [];

        $content = "<?php\n\nreturn [\n";
        $content .= "    'wechat_h5' => [\n";
        $content .= "        'enabled' => ".((int)\Phpcmf\Service::L('input')->post('wechat_enabled') ? 'true' : 'false').",\n";
        $content .= "        'appid' => ".$this->exportString($this->postOrOld('appid', $oldWechat['appid'] ?? '')).",\n";
        $content .= "        'mchid' => ".$this->exportString($this->postOrOld('mchid', $oldWechat['mchid'] ?? '')).",\n";
        $content .= "        'merchant_serial_no' => ".$this->exportString($this->postOrOld('merchant_serial_no', $oldWechat['merchant_serial_no'] ?? '')).",\n";
        $content .= "        'merchant_private_key' => ".$this->exportPath($this->postOrOld('merchant_private_key', $oldWechat['merchant_private_key'] ?? '') ?: "WRITEPATH.'pay/wechat/apiclient_key.pem'").",\n";
        $content .= "        'platform_public_key' => ".$this->exportPath($this->postOrOld('platform_public_key', $oldWechat['platform_public_key'] ?? '') ?: "WRITEPATH.'pay/wechat/wechatpay_public.pem'").",\n";
        $content .= "        'api_v3_key' => ".$this->exportString($this->postOrOld('api_v3_key', $oldWechat['api_v3_key'] ?? '')).",\n";
        $content .= "        'notify_url' => ".$this->exportUrl($this->postOrOld('notify_url', $oldWechat['notify_url'] ?? '') ?: "SITE_URL.'wechatpay_notify.php'").",\n";
        $content .= "        'return_url' => ".$this->exportUrl($this->postOrOld('return_url', $oldWechat['return_url'] ?? '') ?: "SITE_URL.'index.php?s=shop&c=pay&m=return_page&out_trade_no={out_trade_no}'").",\n";
        $content .= "        'scene_info' => [\n";
        $content .= "            'type' => 'Wap',\n";
        $content .= "            'wap_url' => ".$this->exportUrl($this->postOrOld('wap_url', $oldWechat['scene_info']['wap_url'] ?? '') ?: 'SITE_URL').",\n";
        $content .= "            'wap_name' => ".$this->exportUrl($this->postOrOld('wap_name', $oldWechat['scene_info']['wap_name'] ?? '') ?: 'SITE_NAME').",\n";
        $content .= "        ],\n";
        $content .= "    ],\n";
        $content .= "    'alipay_wap' => [\n";
        $content .= "        'enabled' => ".((int)\Phpcmf\Service::L('input')->post('alipay_enabled') ? 'true' : 'false').",\n";
        $content .= "        'account_id' => ".$this->exportString($this->postOrOld('alipay_account_id', $oldAlipay['account_id'] ?? $oldAlipay['seller_id'] ?? '')).",\n";
        $content .= "        'seller_id' => ".$this->exportString($this->postOrOld('alipay_seller_id', $oldAlipay['seller_id'] ?? $oldAlipay['account_id'] ?? '')).",\n";
        $content .= "        'app_id' => ".$this->exportString($this->postOrOld('alipay_app_id', $oldAlipay['app_id'] ?? '')).",\n";
        $content .= "        'app_private_key' => ".$this->exportPath($this->postOrOld('alipay_app_private_key', $oldAlipay['app_private_key'] ?? $oldAlipay['merchant_private_key'] ?? '') ?: "WRITEPATH.'pay/alipay/app_private_key.pem'").",\n";
        $content .= "        'merchant_private_key' => ".$this->exportPath($this->postOrOld('alipay_app_private_key', $oldAlipay['app_private_key'] ?? $oldAlipay['merchant_private_key'] ?? '') ?: "WRITEPATH.'pay/alipay/app_private_key.pem'").",\n";
        $content .= "        'app_public_key' => ".$this->exportPath($this->postOrOld('alipay_app_public_key', $oldAlipay['app_public_key'] ?? '') ?: "WRITEPATH.'pay/alipay/app_public_key.pem'").",\n";
        $content .= "        'alipay_public_key' => ".$this->exportPath($this->postOrOld('alipay_public_key', $oldAlipay['alipay_public_key'] ?? '') ?: "WRITEPATH.'pay/alipay/alipay_public_key.pem'").",\n";
        $content .= "        'notify_url' => ".$this->exportUrl($this->postOrOld('alipay_notify_url', $oldAlipay['notify_url'] ?? '') ?: "SITE_URL.'index.php?s=shop&c=pay&m=alipay_notify'").",\n";
        $content .= "        'return_url' => ".$this->exportUrl($this->postOrOld('alipay_return_url', $oldAlipay['return_url'] ?? '') ?: "SITE_URL.'index.php?s=shop&c=pay&m=alipay_return&out_trade_no={out_trade_no}'").",\n";
        $content .= "        'gateway' => ".$this->exportString($this->postOrOld('alipay_gateway', $oldAlipay['gateway'] ?? '') ?: 'https://openapi.alipay.com/gateway.do').",\n";
        $content .= "        'product_code' => ".$this->exportString($this->postOrOld('alipay_product_code', $oldAlipay['product_code'] ?? '') ?: 'QUICK_WAP_WAY').",\n";
        $content .= "        'sign_type' => ".$this->exportString($this->postOrOld('alipay_sign_type', $oldAlipay['sign_type'] ?? '') ?: 'RSA2').",\n";
        $content .= "        'charset' => ".$this->exportString($this->postOrOld('alipay_charset', $oldAlipay['charset'] ?? '') ?: 'utf-8').",\n";
        $content .= "        'quit_url' => ".$this->exportUrl($this->postOrOld('alipay_quit_url', $oldAlipay['quit_url'] ?? '') ?: 'SITE_URL').",\n";
        $content .= "    ],\n";
        $content .= "];\n";

        if (file_put_contents(APPPATH.'Config/Pay.php', $content) === false) {
            $this->_json(0, '配置文件写入失败，请检查文件权限');
        }

        $this->_json(1, '支付配置已保存');
    }

    private function clean($value)
    {
        return trim(dr_safe_replace((string)$value));
    }

    private function postOrOld($name, $oldValue = '')
    {
        $value = $this->clean(\Phpcmf\Service::L('input')->post($name));
        return $value !== '' ? $value : $oldValue;
    }

    private function exportString($value)
    {
        return var_export((string)$value, true);
    }

    private function exportPath($value)
    {
        if (strpos((string)$value, "WRITEPATH.") === 0 || strpos((string)$value, "ROOTPATH.") === 0 || strpos((string)$value, "WEBPATH.") === 0) {
            return $value;
        }
        return var_export((string)$value, true);
    }

    private function exportUrl($value)
    {
        if ($value === 'SITE_URL' || $value === 'SITE_NAME' || strpos((string)$value, "SITE_URL.") === 0) {
            return $value;
        }
        return var_export((string)$value, true);
    }
}
