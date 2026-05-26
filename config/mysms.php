<?php

/**
 * Custom SMS adapter for XunRuiCMS.
 *
 * The CMS passes the JSON from "SMS settings -> custom parameters" as $third.
 * Do not hard-code AccessKey or template secrets in this file.
 */

function my_sendsms_code($mobile, $code, $third = '') {
    $config = json_decode((string)$third, true);
    if (!$config) {
        return _kraeuterhof_aliyun_sms_return(0, '短信自定义参数不是有效 JSON');
    }

    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $isReset = stripos($uri, 'm=find_code') !== false
        || stripos($uri, 'login/find') !== false
        || stripos($uri, 'reset') !== false
        || stripos($uri, 'password') !== false;

    $templateCode = $isReset
        ? ($config['reset_template_code'] ?? $config['template_code'] ?? '')
        : ($config['register_template_code'] ?? $config['template_code'] ?? '');

    return _kraeuterhof_aliyun_send_sms($mobile, $config, $templateCode, [
        $config['code_var'] ?? 'code' => (string)$code,
    ]);
}

function my_sendsms_text($mobile, $content, $third = '') {
    return _kraeuterhof_aliyun_sms_return(0, '当前短信接口仅支持验证码模板短信');
}

function _kraeuterhof_aliyun_send_sms($mobile, $config, $templateCode, $templateParam) {
    $accessKeyId = trim($config['access_key_id'] ?? '');
    $accessKeySecret = trim($config['access_key_secret'] ?? '');
    $signName = trim($config['sign_name'] ?? '');

    if (!$accessKeyId || !$accessKeySecret || !$signName || !$templateCode) {
        return _kraeuterhof_aliyun_sms_return(0, '阿里云短信参数不完整');
    }

    $params = [
        'AccessKeyId' => $accessKeyId,
        'Action' => 'SendSms',
        'Format' => 'JSON',
        'PhoneNumbers' => $mobile,
        'RegionId' => $config['region_id'] ?? 'cn-hangzhou',
        'SignName' => $signName,
        'SignatureMethod' => 'HMAC-SHA1',
        'SignatureNonce' => uniqid((string)mt_rand(), true),
        'SignatureVersion' => '1.0',
        'TemplateCode' => $templateCode,
        'TemplateParam' => json_encode($templateParam, JSON_UNESCAPED_UNICODE),
        'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        'Version' => '2017-05-25',
    ];

    ksort($params);
    $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    $stringToSign = 'GET&%2F&' . rawurlencode($query);
    $signature = base64_encode(hash_hmac('sha1', $stringToSign, $accessKeySecret . '&', true));
    $url = 'https://dysmsapi.aliyuncs.com/?Signature=' . rawurlencode($signature) . '&' . $query;

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
    } else {
        $response = @file_get_contents($url);
        $error = '';
    }

    if (!$response) {
        return _kraeuterhof_aliyun_sms_return(0, '阿里云短信请求失败：' . $error);
    }

    $result = json_decode($response, true);
    if (($result['Code'] ?? '') === 'OK') {
        return _kraeuterhof_aliyun_sms_return(1, '短信发送成功');
    }

    return _kraeuterhof_aliyun_sms_return(0, '阿里云短信发送失败：' . ($result['Code'] ?? '') . ' ' . ($result['Message'] ?? $response));
}

function _kraeuterhof_aliyun_sms_return($code, $msg, $data = []) {
    if (function_exists('dr_return_data')) {
        return dr_return_data($code, $msg, $data);
    }

    return ['code' => $code, 'msg' => $msg, 'data' => $data];
}
