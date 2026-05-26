<?php namespace Phpcmf\Controllers\Member;

class Address extends \Phpcmf\App
{
    public function index()
    {
        $this->requireMember();
        $this->ensureTable();

        $db = \Phpcmf\Service::M();
        $address = $db->db->table($db->dbprefix('shop_address'))
            ->where('uid', (int)$this->uid)
            ->orderBy('is_default DESC,id DESC')
            ->get()
            ->getRowArray();

        \Phpcmf\Service::V()->assign([
            'address' => $address ?: [],
            'meta_title' => '收货资料',
        ]);
        \Phpcmf\Service::V()->display('address_index.html');
    }

    public function save()
    {
        $this->requireMember();
        $this->ensureTable();

        if (!IS_POST) {
            $this->_msg(0, '非法请求');
        }

        $input = \Phpcmf\Service::L('input');
        $data = [
            'uid' => (int)$this->uid,
            'buyer_name' => dr_safe_replace((string)$input->post('buyer_name')),
            'buyer_phone' => dr_safe_replace((string)$input->post('buyer_phone')),
            'buyer_address' => dr_safe_replace((string)$input->post('buyer_address')),
            'is_default' => 1,
            'updated_at' => time(),
        ];

        if (!$data['buyer_name'] || !$data['buyer_phone'] || !$data['buyer_address']) {
            $this->_msg(0, '请填写收货人、手机号和收货地址');
        }

        $db = \Phpcmf\Service::M();
        $table = $db->dbprefix('shop_address');
        $old = $db->db->table($table)->where('uid', (int)$this->uid)->get()->getRowArray();

        if ($old) {
            $db->db->table($table)->where('id', $old['id'])->update($data);
        } else {
            $data['created_at'] = time();
            $db->db->table($table)->insert($data);
        }

        $this->_msg(1, '收货资料已保存', SITE_URL.'index.php?s=member&app=shop&c=address&m=index', 1);
    }

    private function requireMember()
    {
        $input = \Phpcmf\Service::L('input');
        if (!$this->uid || $input->get_cookie('admin_login_member')) {
            $input->set_cookie('admin_login_member', '', -100000000);
            $this->_msg(0, '请先登录普通会员账号', dr_member_url('login/index', ['back' => urlencode(dr_now_url())]));
        }
    }

    private function ensureTable()
    {
        $db = \Phpcmf\Service::M();
        $table = $db->dbprefix('shop_address');
        if (!$db->is_table_exists($table)) {
            $db->query("CREATE TABLE IF NOT EXISTS `{$table}` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `uid` int(10) unsigned NOT NULL DEFAULT '0',
              `buyer_name` varchar(64) NOT NULL DEFAULT '',
              `buyer_phone` varchar(32) NOT NULL DEFAULT '',
              `buyer_address` varchar(255) NOT NULL DEFAULT '',
              `is_default` tinyint(1) NOT NULL DEFAULT '1',
              `created_at` int(10) unsigned NOT NULL DEFAULT '0',
              `updated_at` int(10) unsigned NOT NULL DEFAULT '0',
              PRIMARY KEY (`id`),
              KEY `uid` (`uid`),
              KEY `is_default` (`is_default`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }
}
