<?php namespace Phpcmf\Controllers;

class Home extends \Phpcmf\App
{
    public function create()
    {
        if (!IS_POST) {
            $this->_msg(0, '请从产品详情页提交订单', SITE_URL);
        }

        $shop = require APPPATH.'Config/Shop.php';
        $payConfig = is_file(APPPATH.'Config/Pay.php') ? require APPPATH.'Config/Pay.php' : [];
        if (!empty($shop['require_member_login'])) {
            $this->requireNormalMember();
        }

        $input = \Phpcmf\Service::L('input');
        $productId = max(0, (int)$input->post('product_id'));
        $quantity = max(1, min(999, (int)$input->post('quantity')));
        $sku = dr_safe_replace((string)$input->post('sku'));
        $payType = $this->resolvePayType((string)$input->post('pay_type'), $payConfig);

        if (!$productId) {
            $this->_msg(0, '产品参数不存在');
        }

        $db = \Phpcmf\Service::M();
        $table = $db->dbprefix($shop['product_table']);
        if (!$db->is_table_exists($table)) {
            $this->_msg(0, '产品数据表不存在');
        }

        $product = $db->db->table($table)->where('id', $productId)->where('status', 9)->get()->getRowArray();
        if (!$product) {
            $this->_msg(0, '产品不存在或未发布');
        }

        $skuPrice = $this->resolveSkuPrice($product, $shop, $sku);
        if ($sku && !$skuPrice) {
            $this->_msg(0, '当前规格不存在或未设置价格，请重新选择规格');
        }

        if ($skuPrice) {
            $sku = $skuPrice['name'];
            $unitPrice = $skuPrice['after'];
        } else {
            $unitPrice = $this->resolvePrice($product, $shop);
        }
        if ($unitPrice === null || $unitPrice <= 0) {
            $this->_msg(0, '当前商品未设置价格，请先在后台填写商品价格');
        }
        $total = number_format($unitPrice * $quantity, 2, '.', '');
        $now = time();
        $outTradeNo = date('YmdHis').$this->uid.random_int(10000, 99999);

        $buyerName = dr_safe_replace((string)$input->post('buyer_name'));
        $buyerPhone = dr_safe_replace((string)$input->post('buyer_phone'));
        $buyerAddress = dr_safe_replace((string)$input->post('buyer_address'));
        $defaultAddress = $this->defaultAddress();
        if ((!$buyerName || !$buyerPhone || !$buyerAddress) && $defaultAddress) {
            $buyerName = $buyerName ?: (string)$defaultAddress['buyer_name'];
            $buyerPhone = $buyerPhone ?: (string)$defaultAddress['buyer_phone'];
            $buyerAddress = $buyerAddress ?: (string)$defaultAddress['buyer_address'];
        }

        $order = [
            'siteid' => defined('SITE_ID') ? SITE_ID : 1,
            'uid' => (int)$this->uid,
            'out_trade_no' => $outTradeNo,
            'product_mid' => $shop['product_module'],
            'product_id' => $productId,
            'product_title' => (string)$product['title'],
            'product_thumb' => (string)($product['thumb'] ?? ''),
            'sku' => $sku,
            'quantity' => $quantity,
            'unit_price' => number_format($unitPrice, 2, '.', ''),
            'total_amount' => $total,
            'pay_amount' => $total,
            'pay_type' => $payType,
            'pay_status' => 0,
            'order_status' => 0,
            'buyer_name' => $buyerName,
            'buyer_phone' => $buyerPhone,
            'buyer_address' => $buyerAddress,
            'remark' => dr_safe_replace((string)$input->post('remark')),
            'client_ip' => $this->clientIp(),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (!$order['buyer_name'] || !$order['buyer_phone'] || !$order['buyer_address']) {
            $this->_msg(0, '请填写收货人、手机号和收货地址');
        }

        $db->db->table($db->dbprefix('shop_order'))->insert($order);
        $this->_msg(1, '订单创建成功，正在进入支付', SITE_URL.'index.php?s=shop&c=pay&m=index&out_trade_no='.$outTradeNo, 1);
    }

    public function logout()
    {
        foreach (['member_uid', 'member_cookie', 'admin_login_member'] as $name) {
            setcookie($name, '', time() - 3600, '/');
            setcookie($name, '', time() - 3600, (defined('WEB_DIR') && WEB_DIR) ? WEB_DIR : '/');
            unset($_COOKIE[$name]);
        }

        $session = $this->session();
        foreach (['member_auth_uid', 'admin_login_member_code'] as $name) {
            if (method_exists($session, 'remove')) {
                $session->remove($name);
            }
        }

        header('Location: '.SITE_URL);
        exit;
    }

    private function requireNormalMember()
    {
        $input = \Phpcmf\Service::L('input');
        if (!$this->uid || $input->get_cookie('admin_login_member')) {
            $input->set_cookie('admin_login_member', '', -100000000);
            $this->_msg(0, '请先登录普通会员账号后再购买', dr_member_url('login/index', [
                'back' => urlencode($this->backUrl()),
            ]));
        }
    }

    private function backUrl()
    {
        $referer = isset($_SERVER['HTTP_REFERER']) ? dr_safe_url($_SERVER['HTTP_REFERER']) : '';
        if (!$referer) {
            return SITE_URL;
        }

        $siteHost = parse_url(SITE_URL, PHP_URL_HOST);
        $refererHost = parse_url($referer, PHP_URL_HOST);
        if ($siteHost && $refererHost && $siteHost !== $refererHost) {
            return SITE_URL;
        }
        if (strpos($referer, 's=shop') !== false && strpos($referer, 'm=create') !== false) {
            return SITE_URL;
        }

        return $referer;
    }

    private function resolvePrice(array $product, array $shop)
    {
        foreach ($shop['price_fields'] as $field) {
            if (isset($product[$field]) && (float)$product[$field] > 0) {
                return (float)$product[$field];
            }
        }

        return null;
    }

    private function resolveSkuPrice(array $product, array $shop, $sku)
    {
        $sku = trim((string)$sku);
        if ($sku === '') {
            return null;
        }

        $field = !empty($shop['sku_price_field']) ? $shop['sku_price_field'] : 'sku_price_text';
        $items = $this->parseSkuPrices((string)($product[$field] ?? ''));
        if (!$items) {
            return null;
        }

        foreach ($items as $item) {
            if (strcasecmp($item['name'], $sku) === 0) {
                return $item;
            }
        }

        return null;
    }

    private function parseSkuPrices($raw)
    {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return [];
        }

        $items = [];
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $name = trim((string)($row['name'] ?? $row['sku'] ?? ''));
                $before = (float)($row['before'] ?? $row['coupon_before_price'] ?? $row['original_price'] ?? 0);
                $after = (float)($row['after'] ?? $row['coupon_after_price'] ?? $row['price'] ?? 0);
                if ($name !== '' && $after > 0) {
                    $items[] = ['name' => $name, 'before' => $before > 0 ? $before : $after, 'after' => $after];
                }
            }
            return $items;
        }

        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', preg_split('/[|,，\t]+/', $line));
            if (count($parts) < 3) {
                $parts = array_map('trim', preg_split('/\s+/', $line));
            }
            if (count($parts) < 3) {
                continue;
            }

            $name = (string)$parts[0];
            $before = (float)$parts[1];
            $after = (float)$parts[2];
            if ($name !== '' && $after > 0) {
                $items[] = ['name' => $name, 'before' => $before > 0 ? $before : $after, 'after' => $after];
            }
        }

        return $items;
    }

    private function resolvePayType($payType, array $payConfig)
    {
        $payType = dr_safe_replace((string)$payType);
        $allowed = ['wechat_h5', 'alipay_wap'];

        if (!$payType) {
            foreach ($allowed as $type) {
                if (!empty($payConfig[$type]['enabled'])) {
                    return $type;
                }
            }
            $this->_msg(0, '暂无可用支付方式，请联系管理员检查支付配置');
        }

        if (!in_array($payType, $allowed, true)) {
            $this->_msg(0, '不支持的支付方式');
        }

        if (empty($payConfig[$payType]['enabled'])) {
            $this->_msg(0, '所选支付方式暂不可用，请重新选择');
        }

        return $payType;
    }

    private function defaultAddress()
    {
        if (!$this->uid) {
            return [];
        }

        $db = \Phpcmf\Service::M();
        $table = $db->dbprefix('shop_address');
        if (!$db->is_table_exists($table)) {
            return [];
        }

        $address = $db->db->table($table)
            ->where('uid', (int)$this->uid)
            ->orderBy('is_default DESC,id DESC')
            ->get()
            ->getRowArray();

        return $address ?: [];
    }

    private function clientIp()
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                return trim(explode(',', $_SERVER[$key])[0]);
            }
        }

        return '0.0.0.0';
    }
}
