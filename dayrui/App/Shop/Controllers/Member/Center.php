<?php namespace Phpcmf\Controllers\Member;

class Center extends \Phpcmf\App
{
    public function index()
    {
        $this->requireMember();

        $db = \Phpcmf\Service::M();
        $orderTable = $db->dbprefix('shop_order');
        $addressTable = $db->dbprefix('shop_address');
        $profileTable = $db->dbprefix('shop_profile');

        $orders = [];
        $orderCount = 0;
        $pendingPayCount = 0;
        $paidCount = 0;
        $address = [];
        $profile = [];

        if ($db->is_table_exists($orderTable)) {
            $orderCount = $db->db->table($orderTable)
                ->where('uid', (int)$this->uid)
                ->countAllResults();
            $pendingPayCount = $db->db->table($orderTable)
                ->where('uid', (int)$this->uid)
                ->where('pay_status', 0)
                ->countAllResults();
            $paidCount = $db->db->table($orderTable)
                ->where('uid', (int)$this->uid)
                ->where('pay_status', 1)
                ->countAllResults();
            $orders = $db->db->table($orderTable)
                ->where('uid', (int)$this->uid)
                ->orderBy('id DESC')
                ->limit(5)
                ->get()
                ->getResultArray();
        }

        if ($db->is_table_exists($addressTable)) {
            $address = $db->db->table($addressTable)
                ->where('uid', (int)$this->uid)
                ->orderBy('is_default DESC,id DESC')
                ->get()
                ->getRowArray();
        }

        if ($db->is_table_exists($profileTable)) {
            $profile = $db->db->table($profileTable)
                ->where('uid', (int)$this->uid)
                ->get()
                ->getRowArray();
        }

        $member = property_exists($this, 'member') && is_array($this->member) ? $this->member : [];
        $displayName = !empty($profile['display_name'])
            ? $profile['display_name']
            : (!empty($member['username']) ? $member['username'] : '会员');

        \Phpcmf\Service::V()->assign([
            'shop_member' => $member,
            'profile' => $profile ?: [],
            'display_name' => $displayName,
            'address' => $address ?: [],
            'orders' => $orders,
            'stats' => [
                'order_count' => $orderCount,
                'pending_pay_count' => $pendingPayCount,
                'paid_count' => $paidCount,
            ],
            'meta_title' => '会员中心',
        ]);
        \Phpcmf\Service::V()->display('center_index.html');
    }

    private function requireMember()
    {
        $input = \Phpcmf\Service::L('input');
        if (!$this->uid || $input->get_cookie('admin_login_member')) {
            $input->set_cookie('admin_login_member', '', -100000000);
            $this->_msg(0, '请先登录普通会员账号', dr_member_url('login/index', ['back' => urlencode(dr_now_url())]));
        }
    }
}
