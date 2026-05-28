<?php namespace Phpcmf\Controllers\Admin;

class Order extends \Phpcmf\App
{
    public function index()
    {
        $db = \Phpcmf\Service::M();
        $input = \Phpcmf\Service::L('input');
        $keyword = dr_safe_replace((string)$input->get('keyword'));
        $payStatus = $input->get('pay_status');

        $builder = $db->db->table($db->dbprefix('shop_order'));
        if ($keyword) {
            $builder->groupStart()
                ->like('out_trade_no', $keyword)
                ->orLike('product_title', $keyword)
                ->orLike('buyer_phone', $keyword)
                ->orLike('buyer_name', $keyword)
                ->groupEnd();
        }
        if ($payStatus !== null && $payStatus !== '') {
            $builder->where('pay_status', (int)$payStatus);
        }

        $orders = $builder->orderBy('id DESC')->limit(100)->get()->getResultArray();
        $shop = require APPPATH.'Config/Shop.php';

        \Phpcmf\Service::V()->assign([
            'orders' => $orders,
            'keyword' => $keyword,
            'pay_status' => $payStatus,
            'allow_admin_mark_paid' => !empty($shop['allow_admin_mark_paid']),
        ]);
        \Phpcmf\Service::V()->display('order_index.html');
    }

    public function mark_paid()
    {
        $shop = require APPPATH.'Config/Shop.php';
        if (empty($shop['allow_admin_mark_paid'])) {
            $this->_json(0, '当前环境不允许手动标记支付');
        }

        $id = (int)\Phpcmf\Service::L('input')->get('id');
        if (!$id) {
            $this->_json(0, '订单参数不存在');
        }

        $db = \Phpcmf\Service::M();
        $order = $db->db->table($db->dbprefix('shop_order'))->where('id', $id)->get()->getRowArray();
        if (!$order) {
            $this->_json(0, '订单不存在');
        }
        if ((int)$order['pay_status'] === 1) {
            $this->_json(1, '订单已经是已支付状态');
        }

        $db->db->table($db->dbprefix('shop_order'))->where('id', $id)->update([
            'pay_status' => 1,
            'order_status' => 1,
            'paid_at' => time(),
            'updated_at' => time(),
        ]);
        $this->_json(1, '已标记为已支付');
    }

    public function ship()
    {
        $id = (int)\Phpcmf\Service::L('input')->get('id');
        if (!$id) {
            $this->_json(0, '订单参数不存在');
        }

        $db = \Phpcmf\Service::M();
        $updated = $db->db->table($db->dbprefix('shop_order'))->where('id', $id)->where('pay_status', 1)->update([
            'order_status' => 2,
            'updated_at' => time(),
        ]);
        if (!$updated) {
            $this->_json(0, '只有已支付订单才能标记发货');
        }
        $this->_json(1, '已标记发货');
    }

    public function delete_unpaid()
    {
        $id = (int)\Phpcmf\Service::L('input')->get('id');
        if (!$id) {
            $this->_json(0, '订单参数不存在');
        }

        $db = \Phpcmf\Service::M();
        $order = $db->db->table($db->dbprefix('shop_order'))->where('id', $id)->get()->getRowArray();
        if (!$order) {
            $this->_json(0, '订单不存在');
        }

        if ((int)$order['pay_status'] === 1) {
            $this->_json(0, '已支付订单不允许删除，请保留财务记录');
        }

        $db->db->table($db->dbprefix('shop_order'))->where('id', $id)->where('pay_status', 0)->delete();
        $this->_json(1, '未支付订单已删除');
    }
}
