<?php namespace Phpcmf\Controllers;

class Pay extends \Phpcmf\App
{
    public function index()
    {
        $outTradeNo = dr_safe_replace((string)\Phpcmf\Service::L('input')->get('out_trade_no'));
        $order = $this->order($outTradeNo);
        if (!$order) {
            $this->_msg(0, '订单不存在');
        }

        $this->requireOrderOwner($order);
        if ((int)$order['pay_status'] === 1) {
            $this->_msg(1, '订单已支付', SITE_URL.'index.php?s=shop&c=pay&m=return_page&out_trade_no='.$outTradeNo);
        }

        $config = require APPPATH.'Config/Pay.php';
        $payType = !empty($order['pay_type']) ? (string)$order['pay_type'] : 'wechat_h5';

        if ($payType === 'alipay_wap') {
            $this->alipayPay($order, $config['alipay_wap'] ?? []);
            return;
        }

        if ($payType !== 'wechat_h5') {
            $this->_msg(0, '不支持的支付方式');
        }

        $this->wechatPay($order, $config['wechat_h5'] ?? []);
    }

    public function notify()
    {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!$payload || empty($payload['resource'])) {
            $this->wechatResponse(400, 'invalid payload');
        }

        require_once APPPATH.'Libraries/WechatH5.php';
        $config = require APPPATH.'Config/Pay.php';

        try {
            $pay = new \Phpcmf\Library\Shop\WechatH5($config['wechat_h5'] ?? []);
            $pay->verifyNotifySignature($raw, $_SERVER);
            $data = $pay->decryptNotify($payload['resource']);
        } catch (\Throwable $e) {
            log_message('error', 'WeChat H5 Pay notify decrypt failed: '.$e->getMessage());
            $this->wechatResponse(400, 'decrypt failed');
        }

        if (($data['trade_state'] ?? '') !== 'SUCCESS') {
            $this->wechatResponse(200, 'ignored');
        }

        $outTradeNo = dr_safe_replace((string)($data['out_trade_no'] ?? ''));
        $order = $this->order($outTradeNo);
        if (!$order) {
            $this->wechatResponse(404, 'order not found');
        }

        if (isset($data['amount']['total'])) {
            $paidCents = (int)$data['amount']['total'];
            $orderCents = (int)round((float)$order['pay_amount'] * 100);
            if ($paidCents !== $orderCents) {
                log_message('error', 'WeChat H5 Pay amount mismatch: '.$outTradeNo);
                $this->wechatResponse(400, 'amount mismatch');
            }
        }

        if ((int)$order['pay_status'] !== 1) {
            $this->markOrderPaid($order, [
                'transaction_id' => dr_safe_replace((string)($data['transaction_id'] ?? '')),
                'payer_openid' => dr_safe_replace((string)($data['payer']['openid'] ?? '')),
                'notify_raw' => $raw,
            ]);
        }

        $this->wechatResponse(200, 'success');
    }

    public function alipay_notify()
    {
        $params = $_POST;
        if (!$params) {
            echo 'failure';
            exit;
        }

        $config = require APPPATH.'Config/Pay.php';
        $alipayConfig = $config['alipay_wap'] ?? [];
        require_once APPPATH.'Libraries/AlipayWap.php';

        try {
            $pay = new \Phpcmf\Library\Shop\AlipayWap($alipayConfig);
            if (!$pay->verify($params)) {
                log_message('error', 'Alipay WAP notify verify failed: '.json_encode($params, JSON_UNESCAPED_UNICODE));
                echo 'failure';
                exit;
            }
        } catch (\Throwable $e) {
            log_message('error', 'Alipay WAP notify exception: '.$e->getMessage());
            echo 'failure';
            exit;
        }

        if (!empty($alipayConfig['app_id']) && !empty($params['app_id']) && (string)$params['app_id'] !== (string)$alipayConfig['app_id']) {
            log_message('error', 'Alipay WAP app_id mismatch: '.(string)($params['out_trade_no'] ?? ''));
            echo 'failure';
            exit;
        }

        if (!empty($alipayConfig['seller_id']) && !empty($params['seller_id']) && (string)$params['seller_id'] !== (string)$alipayConfig['seller_id']) {
            log_message('error', 'Alipay WAP seller_id mismatch: '.(string)($params['out_trade_no'] ?? ''));
            echo 'failure';
            exit;
        }

        $tradeStatus = (string)($params['trade_status'] ?? '');
        if (!in_array($tradeStatus, ['TRADE_SUCCESS', 'TRADE_FINISHED'], true)) {
            echo 'success';
            exit;
        }

        $outTradeNo = dr_safe_replace((string)($params['out_trade_no'] ?? ''));
        $order = $this->order($outTradeNo);
        if (!$order) {
            echo 'failure';
            exit;
        }

        if (number_format((float)$order['pay_amount'], 2, '.', '') !== number_format((float)($params['total_amount'] ?? 0), 2, '.', '')) {
            log_message('error', 'Alipay WAP amount mismatch: '.$outTradeNo);
            echo 'failure';
            exit;
        }

        if ((int)$order['pay_status'] !== 1) {
            $this->markOrderPaid($order, [
                'transaction_id' => dr_safe_replace((string)($params['trade_no'] ?? '')),
                'payer_openid' => '',
                'notify_raw' => json_encode($params, JSON_UNESCAPED_UNICODE),
            ]);
        }

        echo 'success';
        exit;
    }

    public function return_page()
    {
        $this->displayReturnPage((string)\Phpcmf\Service::L('input')->get('out_trade_no'));
    }

    public function alipay_return()
    {
        $this->displayReturnPage((string)\Phpcmf\Service::L('input')->get('out_trade_no'));
    }

    private function wechatPay(array $order, array $config)
    {
        if (empty($config['enabled'])) {
            $this->_msg(0, '微信 H5 支付暂不可用，请联系管理员检查支付配置', SITE_URL.'index.php?s=member&app=shop&c=order&m=index');
        }

        require_once APPPATH.'Libraries/WechatH5.php';

        try {
            $pay = new \Phpcmf\Library\Shop\WechatH5($config);
            $result = $pay->createOrder($order);
        } catch (\Throwable $e) {
            log_message('error', 'WeChat H5 Pay create failed: '.$e->getMessage());
            $this->_msg(0, '微信 H5 支付暂不可用，请联系管理员检查支付配置');
        }

        if (empty($result['h5_url'])) {
            $this->_msg(0, '支付平台未返回 H5 支付链接');
        }

        header('Location: '.$result['h5_url']);
        exit;
    }

    private function alipayPay(array $order, array $config)
    {
        if (empty($config['enabled'])) {
            $this->_msg(0, '支付宝 H5 支付暂不可用，请联系管理员检查支付配置', SITE_URL.'index.php?s=member&app=shop&c=order&m=index');
        }

        require_once APPPATH.'Libraries/AlipayWap.php';

        try {
            $pay = new \Phpcmf\Library\Shop\AlipayWap($config);
            echo $pay->createOrder($order);
            exit;
        } catch (\Throwable $e) {
            log_message('error', 'Alipay WAP create failed: '.$e->getMessage());
            $this->_msg(0, '支付宝 H5 支付暂不可用，请联系管理员检查支付配置');
        }
    }

    private function displayReturnPage($outTradeNo)
    {
        $outTradeNo = dr_safe_replace((string)$outTradeNo);
        $order = $this->order($outTradeNo);
        if ($order) {
            $this->requireOrderOwner($order);
        }

        \Phpcmf\Service::V()->assign([
            'order' => $order,
            'meta_title' => '支付结果',
        ]);
        \Phpcmf\Service::V()->display('pay_return.html');
    }

    private function order($outTradeNo)
    {
        if (!$outTradeNo) {
            return [];
        }

        $db = \Phpcmf\Service::M();
        return $db->db->table($db->dbprefix('shop_order'))->where('out_trade_no', $outTradeNo)->get()->getRowArray();
    }

    private function requireOrderOwner(array $order)
    {
        $input = \Phpcmf\Service::L('input');
        if (!$this->uid || $input->get_cookie('admin_login_member')) {
            $input->set_cookie('admin_login_member', '', -100000000);
            $this->_msg(0, '请先登录普通会员账号', dr_member_url('login/index', ['back' => urlencode(dr_now_url())]));
        }

        if ((int)$order['uid'] !== (int)$this->uid) {
            $this->_msg(0, '无权访问此订单', SITE_URL);
        }
    }

    private function markOrderPaid(array $order, array $data)
    {
        $db = \Phpcmf\Service::M();
        $db->db->table($db->dbprefix('shop_order'))->where('id', $order['id'])->update([
            'pay_status' => 1,
            'order_status' => 1,
            'transaction_id' => (string)($data['transaction_id'] ?? ''),
            'payer_openid' => (string)($data['payer_openid'] ?? ''),
            'notify_raw' => (string)($data['notify_raw'] ?? ''),
            'paid_at' => time(),
            'updated_at' => time(),
        ]);
    }

    private function wechatResponse($code, $message)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => $code === 200 ? 'SUCCESS' : 'FAIL', 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
