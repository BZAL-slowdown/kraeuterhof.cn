<?php namespace Phpcmf\Controllers;

class Register extends \Phpcmf\App
{
    private $codeTtl = 600;
    private $sendLockTtl = 60;

    public function index()
    {
        \Phpcmf\Service::V()->assign([
            'meta_title' => '会员注册',
            'send_code_url' => '/index.php?s=shop&c=register&m=send_code',
            'register_url' => '/index.php?s=shop&c=register&m=create',
            'login_url' => '/index.php?s=shop&c=login&m=index&back='.rawurlencode('/index.php?s=member&app=shop&c=center&m=index'),
            'center_url' => SITE_URL.'index.php?s=member&app=shop&c=center&m=index',
        ]);
        \Phpcmf\Service::V()->display('register_index.html');
    }

    public function send_code()
    {
        if (!IS_POST) {
            $this->_json(0, '非法请求');
        }

        $account = $this->account();
        $type = $this->accountType($account);
        if (!$type) {
            $this->_json(0, '请填写正确的手机号或邮箱');
        }

        $db = \Phpcmf\Service::M();
        if ($type === 'phone' && $db->db->table('member')->where('phone', $account)->countAllResults()) {
            $this->_json(0, '该手机号已经注册');
        }
        if ($type === 'email' && $db->db->table('member')->where('email', strtolower($account))->countAllResults()) {
            $this->_json(0, '该邮箱已经注册');
        }

        $key = $this->codeKey($account);
        $lockKey = $this->lockKey($account);
        if (\Phpcmf\Service::L('cache')->get_auth_data($lockKey, SITE_ID, $this->sendLockTtl)) {
            $this->_json(0, '验证码发送太频繁，请稍后再试');
        }

        $code = (string)random_int(100000, 999999);
        if ($type === 'phone') {
            $rt = \Phpcmf\Service::M('member')->sendsms_code($account, $code);
        } else {
            $subject = '七叶庄园注册验证码';
            $content = '您的注册验证码是：'.$code.'，10分钟内有效。若非本人操作，请忽略本邮件。';
            $this->ensureEmailCache();
            $rt = \Phpcmf\Service::M('email')->sendmail(strtolower($account), $subject, $content);
        }

        if (empty($rt['code'])) {
            $msg = !empty($rt['msg']) ? $rt['msg'] : '验证码发送失败，请检查短信或邮件配置';
            $this->_json(0, $msg);
        }

        \Phpcmf\Service::L('cache')->set_auth_data($key, [
            'code' => $code,
            'type' => $type,
            'account' => $account,
            'time' => SYS_TIME,
        ]);
        \Phpcmf\Service::L('cache')->set_auth_data($lockKey, '1');

        $this->_json(1, '验证码已发送，请注意查收');
    }

    public function create()
    {
        if (!IS_POST) {
            $this->_json(0, '非法请求');
        }

        $input = \Phpcmf\Service::L('input');
        $account = $this->account();
        $type = $this->accountType($account);
        $code = trim((string)$input->post('code'));
        $password = (string)$input->post('password');
        $password2 = (string)$input->post('password2');
        $displayName = trim(dr_safe_replace((string)$input->post('display_name')));

        if (!$type) {
            $this->_json(0, '请填写正确的手机号或邮箱');
        }
        if (!$code) {
            $this->_json(0, '请填写验证码');
        }
        if ($password === '' || $password !== $password2) {
            $this->_json(0, '两次输入的密码不一致');
        }
        if ($displayName !== '' && mb_strlen($displayName, 'UTF-8') > 30) {
            $this->_json(0, '用户名称不能超过30个字');
        }

        $cache = \Phpcmf\Service::L('cache')->get_auth_data($this->codeKey($account), SITE_ID, $this->codeTtl);
        if (!$cache || empty($cache['code']) || $cache['code'] !== $code || $cache['account'] !== $account) {
            $this->_json(0, '验证码不正确或已过期');
        }

        $username = $this->makeUsername($account);
        $member = [
            'username' => $username,
            'password' => $password,
            'name' => $displayName ?: $username,
            'email' => $type === 'email' ? strtolower($account) : '',
            'phone' => $type === 'phone' ? $account : '',
        ];

        $rt = \Phpcmf\Service::M('member')->register(0, $member, []);
        if (empty($rt['code'])) {
            $this->_json(0, !empty($rt['msg']) ? $rt['msg'] : '注册失败，请稍后再试');
        }

        $uid = (int)$rt['code'];
        $this->markVerified($uid, $type);
        $this->saveProfile($uid, $displayName ?: $member['name']);
        \Phpcmf\Service::L('cache')->del_auth_data($this->codeKey($account));

        $login = \Phpcmf\Service::M('member')->login($username, $password, 0);
        $url = SITE_URL.'index.php?s=member&app=shop&c=center&m=index';
        if (empty($login['code'])) {
            $this->_json(1, '注册成功，请登录会员中心', [
                'url' => '/index.php?s=shop&c=login&m=index&back='.rawurlencode($url),
            ]);
        }

        $this->_json(1, '注册成功', ['url' => $url]);
    }

    private function account()
    {
        return trim((string)\Phpcmf\Service::L('input')->post('account'));
    }

    private function accountType($account)
    {
        if (preg_match('/^1[3-9]\d{9}$/', $account)) {
            return 'phone';
        }
        if (filter_var($account, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }
        return '';
    }

    private function codeKey($account)
    {
        return 'shop_register_code_'.md5(strtolower($account));
    }

    private function lockKey($account)
    {
        return 'shop_register_lock_'.md5(strtolower($account));
    }

    private function makeUsername($account)
    {
        $base = preg_match('/^1[3-9]\d{9}$/', $account)
            ? $account
            : preg_replace('/[^a-zA-Z0-9_]/', '', strtolower((string)strstr($account, '@', true)));
        $base = trim($base ?: 'member', '_');
        $base = dr_safe_filename($base);
        $username = strtolower(substr($base, 0, 18));

        $db = \Phpcmf\Service::M();
        if (!$username || $db->db->table('member')->where('username', $username)->countAllResults()) {
            $username = 'm'.date('ymd').substr(md5(strtolower($account).SYS_TIME.random_int(1000, 9999)), 0, 8);
        }

        return $username;
    }

    private function markVerified($uid, $type)
    {
        $data = ['is_verify' => 1];
        if ($type === 'phone') {
            $data['is_mobile'] = 1;
        }
        if ($type === 'email') {
            $data['is_email'] = 1;
        }
        \Phpcmf\Service::M()->db->table('member_data')->where('id', (int)$uid)->update($data);
    }

    private function saveProfile($uid, $displayName)
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

        $now = SYS_TIME;
        $old = $db->db->table($table)->where('uid', (int)$uid)->get()->getRowArray();
        $data = [
            'uid' => (int)$uid,
            'display_name' => $displayName,
            'updated_at' => $now,
        ];
        if ($old) {
            $db->db->table($table)->where('uid', (int)$uid)->update($data);
        } else {
            $data['created_at'] = $now;
            $db->db->table($table)->insert($data);
        }
    }

    private function ensureEmailCache()
    {
        if (!\Phpcmf\Service::L('cache')->get('email')) {
            \Phpcmf\Service::M('email')->cache(SITE_ID);
        }
    }
}
