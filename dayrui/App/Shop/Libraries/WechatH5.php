<?php namespace Phpcmf\Library\Shop;

class WechatH5
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function createOrder(array $order)
    {
        $this->assertReady();

        $body = [
            'appid' => $this->config['appid'],
            'mchid' => $this->config['mchid'],
            'description' => mb_substr($order['product_title'], 0, 120),
            'out_trade_no' => $order['out_trade_no'],
            'notify_url' => $this->config['notify_url'],
            'amount' => [
                'total' => (int)round(((float)$order['pay_amount']) * 100),
                'currency' => 'CNY',
            ],
            'scene_info' => [
                'payer_client_ip' => $order['client_ip'],
                'h5_info' => [
                    'type' => $this->config['scene_info']['type'] ?: 'Wap',
                    'wap_url' => $this->config['scene_info']['wap_url'] ?: SITE_URL,
                    'wap_name' => $this->config['scene_info']['wap_name'] ?: SITE_NAME,
                ],
            ],
        ];

        return $this->request('POST', '/v3/pay/transactions/h5', $body);
    }

    public function decryptNotify(array $resource)
    {
        if (empty($this->config['api_v3_key'])) {
            throw new \RuntimeException('Missing WeChat Pay API v3 key.');
        }

        $ciphertext = base64_decode($resource['ciphertext']);
        $aad = $resource['associated_data'] ?? '';
        $nonce = $resource['nonce'] ?? '';
        $key = $this->config['api_v3_key'];
        $tag = substr($ciphertext, -16);
        $data = substr($ciphertext, 0, -16);
        $plain = openssl_decrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, $aad);

        if ($plain === false) {
            throw new \RuntimeException('Failed to decrypt WeChat Pay notify data.');
        }

        return json_decode($plain, true);
    }

    public function verifyNotifySignature($rawBody, array $server)
    {
        if (empty($this->config['platform_public_key'])) {
            throw new \RuntimeException('Missing WeChat Pay platform public key.');
        }

        $publicKey = file_get_contents($this->config['platform_public_key']);
        if (!$publicKey) {
            throw new \RuntimeException('WeChat Pay platform public key is unreadable.');
        }

        $timestamp = $server['HTTP_WECHATPAY_TIMESTAMP'] ?? '';
        $nonce = $server['HTTP_WECHATPAY_NONCE'] ?? '';
        $signature = $server['HTTP_WECHATPAY_SIGNATURE'] ?? '';
        if (!$timestamp || !$nonce || !$signature) {
            throw new \RuntimeException('Missing WeChat Pay notify signature headers.');
        }

        $message = $timestamp."\n".$nonce."\n".$rawBody."\n";
        $ok = openssl_verify($message, base64_decode($signature), $publicKey, 'sha256WithRSAEncryption');
        if ($ok !== 1) {
            throw new \RuntimeException('Invalid WeChat Pay notify signature.');
        }
    }

    private function request($method, $path, array $body)
    {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE);
        $timestamp = (string)time();
        $nonce = bin2hex(random_bytes(16));
        $signature = $this->sign($method."\n".$path."\n".$timestamp."\n".$nonce."\n".$json."\n");
        $authorization = sprintf(
            'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",timestamp="%s",serial_no="%s",signature="%s"',
            $this->config['mchid'],
            $nonce,
            $timestamp,
            $this->config['merchant_serial_no'],
            $signature
        );

        $ch = curl_init('https://api.mch.weixin.qq.com'.$path);
        curl_setopt_array($ch, [
            CURLOPT_POST => $method === 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'User-Agent: kraeuterhof.cn WeChatPayClient/1.0',
                'Authorization: '.$authorization,
            ],
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('WeChat Pay request failed: '.$error);
        }

        $data = json_decode($response, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $message = is_array($data) ? ($data['message'] ?? $response) : $response;
            throw new \RuntimeException('WeChat Pay API error: '.$message);
        }

        return $data;
    }

    private function sign($message)
    {
        $privateKey = file_get_contents($this->config['merchant_private_key']);
        if (!$privateKey) {
            throw new \RuntimeException('WeChat merchant private key is unreadable.');
        }

        $ok = openssl_sign($message, $raw, $privateKey, 'sha256WithRSAEncryption');
        if (!$ok) {
            throw new \RuntimeException('Failed to sign WeChat Pay request.');
        }

        return base64_encode($raw);
    }

    private function assertReady()
    {
        foreach (['appid', 'mchid', 'merchant_serial_no', 'merchant_private_key', 'api_v3_key'] as $key) {
            if (empty($this->config[$key])) {
                throw new \RuntimeException('WeChat Pay config missing: '.$key);
            }
        }

        if (empty($this->config['enabled'])) {
            throw new \RuntimeException('WeChat H5 Pay is disabled.');
        }
    }
}
