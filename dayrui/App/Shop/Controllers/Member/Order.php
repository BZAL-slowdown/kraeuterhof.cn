<?php namespace Phpcmf\Controllers\Member;

class Order extends \Phpcmf\App
{
    public function index()
    {
        $input = \Phpcmf\Service::L('input');
        if (!$this->uid || $input->get_cookie('admin_login_member')) {
            $input->set_cookie('admin_login_member', '', -100000000);
            $this->_msg(0, '请先登录普通会员账号', dr_member_url('login/index', ['back' => urlencode(dr_now_url())]));
        }

        $db = \Phpcmf\Service::M();
        $orders = $db->db->table($db->dbprefix('shop_order'))
            ->where('uid', (int)$this->uid)
            ->orderBy('id DESC')
            ->limit(50)
            ->get()
            ->getResultArray();

        \Phpcmf\Service::V()->assign([
            'orders' => $orders,
            'meta_title' => '我的订单',
        ]);
        \Phpcmf\Service::V()->display('order_index.html');
    }
}
