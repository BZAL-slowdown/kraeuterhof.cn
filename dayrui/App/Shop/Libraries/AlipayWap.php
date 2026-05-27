<?php namespace Phpcmf\Library\Shop;

class AlipayWap
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function createOrder(array $order)
    {
        $this->assertCreateConfig();

        $outTradeNo = (string)$order['out_trade_no'];
        $bizContent = [
            'out_trade_no' => $outTradeNo,
            'total_amount' => number_format((float)$order['pay_amount'], 2, '.', ''),
            'subject' => $this->subject((string)$order['product_title']),
            'product_code' => $this->config['product_code'] ?: 'QUICK_WAP_WAY',
            'quit_url' => $this->config['quit_url'] ?? SITE_URL,
        ];

        if (!empty($this->config['seller_id'])) {
            $bizContent['seller_id'] = (string)$this->config['seller_id'];
        }

        $params = [
            'app_id' => (string)$this->config['app_id'],
            'method' => 'alipay.trade.wap.pay',
            'format' => 'JSON',
            'charset' => $this->config['charset'] ?? 'utf-8',
            'sign_type' => $this->config['sign_type'] ?? 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'notify_url' => (string)$this->config['notify_url'],
            'return_url' => str_replace('{out_trade_no}', $outTradeNo, (string)$this->config['return_url']),
            'biz_content' => json_encode($bizContent, JSON_UNESCAPED_UNICODE),
        ];

        $params['sign'] = $this->sign($params);

        $charset = (string)($params['charset'] ?? 'utf-8');
        unset($params['charset']);

        return $this->buildForm($this->gateway($charset), $params);
    }

    public function verify(array $params)
    {
        if (!function_exists('openssl_verify')) {
            throw new \RuntimeException('PHP OpenSSL extension is required for Alipay');
        }

        if (empty($params['sign'])) {
            return false;
        }

        $sign = (string)$params['sign'];
        unset($params['sign'], $params['sign_type']);

        $content = $this->signContent($params);
        $publicKey = $this->publicKey((string)($this->config['alipay_public_key'] ?? ''));
        return \openssl_verify($content, \base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    private function sign(array $params)
    {
        unset($params['sign']);
        $content = $this->signContent($params);
        $privateKey = $this->privateKey((string)($this->config['app_private_key'] ?? $this->config['merchant_private_key'] ?? ''));
        $signature = '';

        if (!\openssl_sign($content, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Alipay sign failed');
        }

        return base64_encode($signature);
    }

    private function signContent(array $params)
    {
        ksort($params);
        $pairs = [];
        foreach ($params as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $pairs[] = $key.'='.$value;
        }
        return implode('&', $pairs);
    }

    private function buildForm($gateway, array $params)
    {
        $html = '<!doctype html><html><head><meta charset="utf-8"><title>正在跳转支付宝支付</title></head><body>';
        $html .= '<form id="alipaysubmit" name="alipaysubmit" action="'.htmlspecialchars($gateway, ENT_QUOTES, 'UTF-8').'" method="POST">';
        foreach ($params as $key => $value) {
            $html .= '<input type="hidden" name="'.htmlspecialchars($key, ENT_QUOTES, 'UTF-8').'" value="'.htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8').'">';
        }
        $html .= '</form><script>document.forms.alipaysubmit.submit();</script>';
        $html .= '<p style="font-family:Arial,sans-serif;text-align:center;margin-top:80px;">正在跳转支付宝支付，请稍候...</p>';
        $html .= '</body></html>';
        return $html;
    }

    private function gateway($charset = 'utf-8')
    {
        $gateway = !empty($this->config['gateway']) ? (string)$this->config['gateway'] : 'https://openapi.alipay.com/gateway.do';
        $separator = strpos($gateway, '?') === false ? '?' : '&';
        return $gateway.$separator.'charset='.rawurlencode($charset ?: 'utf-8');
    }

    private function assertCreateConfig()
    {
        if (!function_exists('openssl_sign')) {
            throw new \RuntimeException('PHP OpenSSL extension is required for Alipay');
        }

        foreach (['app_id', 'notify_url', 'return_url'] as $key) {
            if (empty($this->config[$key])) {
                throw new \RuntimeException('Missing Alipay config: '.$key);
            }
        }

        $key = $this->config['app_private_key'] ?? $this->config['merchant_private_key'] ?? '';
        if (!$key) {
            throw new \RuntimeException('Missing Alipay app private key');
        }
    }

    private function subject($subject)
    {
        $subject = trim($subject) ?: 'Kraeuterhof order';
        if (function_exists('mb_substr')) {
            return mb_substr($subject, 0, 120, 'UTF-8');
        }
        return substr($subject, 0, 120);
    }

    private function readKey($value)
    {
        $value = trim($value);
        if ($value && is_file($value)) {
            return trim((string)file_get_contents($value));
        }
        return $value;
    }

    private function privateKey($value)
    {
        $raw = $this->readKey($value);
        $key = $this->formatKey($raw, 'PRIVATE KEY');
        if (\openssl_pkey_get_private($key)) {
            return $key;
        }

        $rsaKey = $this->formatKey($raw, 'RSA PRIVATE KEY');
        if (\openssl_pkey_get_private($rsaKey)) {
            return $rsaKey;
        }

        throw new \RuntimeException('Invalid Alipay app private key');
    }

    private function publicKey($value)
    {
        $key = $this->formatKey($this->readKey($value), 'PUBLIC KEY');
        if (!\openssl_pkey_get_public($key)) {
            throw new \RuntimeException('Invalid Alipay public key');
        }
        return $key;
    }

    private function formatKey($key, $label)
    {
        $key = trim($key);
        if (strpos($key, 'BEGIN ') !== false) {
            return $key;
        }

        $body = chunk_split(str_replace(["\r", "\n", ' '], '', $key), 64, "\n");
        return "-----BEGIN {$label}-----\n".$body."-----END {$label}-----";
    }
}
