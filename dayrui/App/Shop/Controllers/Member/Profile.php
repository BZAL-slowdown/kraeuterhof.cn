<?php namespace Phpcmf\Controllers\Member;

class Profile extends \Phpcmf\App
{
    public function index()
    {
        $this->requireMember();
        $this->ensureTable();

        $member = $this->memberData();
        $profile = $this->profile();
        $displayName = !empty($profile['display_name'])
            ? $profile['display_name']
            : (!empty($member['username']) ? $member['username'] : '会员');

        \Phpcmf\Service::V()->assign([
            'shop_member' => $member,
            'profile' => $profile,
            'display_name' => $displayName,
            'meta_title' => '个人资料',
        ]);
        \Phpcmf\Service::V()->display('profile_index.html');
    }

    public function save()
    {
        $this->requireMember();
        $this->ensureTable();

        if (!IS_POST) {
            $this->_msg(0, '非法请求');
        }

        $input = \Phpcmf\Service::L('input');
        $displayName = trim(dr_safe_replace((string)$input->post('display_name')));
        if ($displayName === '') {
            $this->_msg(0, '请填写用户名称');
        }
        if (mb_strlen($displayName, 'UTF-8') > 30) {
            $this->_msg(0, '用户名称不能超过30个字符');
        }

        $old = $this->profile();
        $avatar = $old['avatar'] ?? '';
        if (!empty($_FILES['avatar']['name'])) {
            $avatar = $this->uploadAvatar('avatar');
        }

        $now = time();
        $data = [
            'uid' => (int)$this->uid,
            'display_name' => $displayName,
            'avatar' => $avatar,
            'updated_at' => $now,
        ];

        $db = \Phpcmf\Service::M();
        $table = $db->dbprefix('shop_profile');
        if ($old) {
            $db->db->table($table)->where('uid', (int)$this->uid)->update($data);
        } else {
            $data['created_at'] = $now;
            $db->db->table($table)->insert($data);
        }

        $this->_msg(1, '个人资料已保存', SITE_URL.'index.php?s=member&app=shop&c=center&m=index', 1);
    }

    private function uploadAvatar($field)
    {
        $file = $_FILES[$field];
        if (!empty($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            $this->_msg(0, '头像上传失败，请重新选择图片');
        }
        if ((int)$file['size'] > 2 * 1024 * 1024) {
            $this->_msg(0, '头像图片不能超过2MB');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $this->_msg(0, '头像仅支持 jpg、png、gif、webp 格式');
        }

        $info = @getimagesize($file['tmp_name']);
        if (!$info) {
            $this->_msg(0, '请选择有效的图片文件');
        }

        $dir = WEBPATH.'uploadfile/shop/avatar/';
        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            $this->_msg(0, '头像目录不可写');
        }

        $name = (int)$this->uid.'_'.date('YmdHis').'_'.random_int(1000, 9999).'.'.$ext;
        $path = $dir.$name;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            $this->_msg(0, '头像保存失败，请检查目录权限');
        }

        @chmod($path, 0644);
        return '/uploadfile/shop/avatar/'.$name;
    }

    private function profile()
    {
        $db = \Phpcmf\Service::M();
        $table = $db->dbprefix('shop_profile');
        if (!$db->is_table_exists($table)) {
            return [];
        }

        $profile = $db->db->table($table)
            ->where('uid', (int)$this->uid)
            ->get()
            ->getRowArray();

        return $profile ?: [];
    }

    private function ensureTable()
    {
        $db = \Phpcmf\Service::M();
        $table = $db->dbprefix('shop_profile');
        if (!$db->is_table_exists($table)) {
            $db->query("CREATE TABLE IF NOT EXISTS `{$table}` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `uid` int(10) unsigned NOT NULL DEFAULT '0',
              `display_name` varchar(64) NOT NULL DEFAULT '',
              `avatar` varchar(255) NOT NULL DEFAULT '',
              `created_at` int(10) unsigned NOT NULL DEFAULT '0',
              `updated_at` int(10) unsigned NOT NULL DEFAULT '0',
              PRIMARY KEY (`id`),
              UNIQUE KEY `uid` (`uid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    private function memberData()
    {
        return property_exists($this, 'member') && is_array($this->member) ? $this->member : [];
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
