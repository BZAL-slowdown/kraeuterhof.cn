<?php namespace Phpcmf\Controllers;

class Password extends \Phpcmf\App
{
    private $codeTtl = 600;
    private $sendLockTtl = 60;

    public function find()
    {
        \Phpcmf\Service::V()->assign([
            'meta_title' => '找回密码',
            'send_code_url' => '/index.php?s=shop&c=password&m=send_code',
            'reset_url' => '/index.php?s=shop&c=password&m=reset',
            'login_url' => '/index.php?s=shop&c=login&m=index&back='.rawurlencode('/index.php?s=member&app=shop&c=center&m=index'),
            'home_url' => '/',
        ]);
        \Phpcmf\Service::V()->display('password_find.html');
    }

    public function send_code()
    {
        if (!IS_POST) {
            $this->_json(0, '非法请求');
        }
        if (!\Phpcmf\Service::L('form')->check_captcha('captcha')) {
            $this->_json(0, '图片验证码不正确');
        }

        $account = $this->account();
        $type = $this->accountType($account);
        if (!$type) {
            $this->_json(0, '请输入正确的手机号或邮箱');
        }

        $member = $this->findMember($account, $type);
        if (!$member) {
            $this->_json(0, '没有找到这个会员账号');
        }

        $lockKey = $this->lockKey($account);
        if (\Phpcmf\Service::L('cache')->get_auth_data($lockKey, SITE_ID, $this->sendLockTtl)) {
            $this->_json(0, '验证码发送太频繁，请稍后再试');
        }

        $code = (string)random_int(100000, 999999);
        if ($type === 'phone') {
            $rt = \Phpcmf\Service::M('member')->sendsms_code($account, $code);
        } else {
            $subject = '七叶庄园密码重置验证码';
            $content = '您的密码重置验证码是：'.$code.'，10分钟内有效。若非本人操作，请忽略本邮件。';
            $this->ensureEmailCache();
            $rt = \Phpcmf\Service::M('email')->sendmail(strtolower($account), $subject, $content);
        }

        if (empty($rt['code'])) {
            $msg = !empty($rt['msg']) ? $rt['msg'] : '验证码发送失败，请检查短信或邮件配置';
            $this->_json(0, $msg);
        }

        \Phpcmf\Service::L('cache')->set_auth_data($this->codeKey($account), [
            'code' => $code,
            'type' => $type,
            'account' => $account,
            'uid' => (int)$member['id'],
            'time' => SYS_TIME,
        ]);
        \Phpcmf\Service::L('cache')->set_auth_data($lockKey, '1');

        $this->_json(1, '验证码已发送，请注意查收');
    }

    public function reset()
    {
        if (!IS_POST) {
            $this->_json(0, '非法请求');
        }

        $account = $this->account();
        $type = $this->accountType($account);
        $code = trim((string)\Phpcmf\Service::L('input')->post('code'));
        $password = (string)\Phpcmf\Service::L('input')->post('password');
        $password2 = (string)\Phpcmf\Service::L('input')->post('password2');

        if (!$type) {
            $this->_json(0, '请输入正确的手机号或邮箱');
        }
        if (!$code) {
            $this->_json(0, '请填写验证码');
        }
        if ($password === '' || $password !== $password2) {
            $this->_json(0, '两次输入的密码不一致');
        }

        $member = $this->findMember($account, $type);
        if (!$member) {
            $this->_json(0, '没有找到这个会员账号');
        }

        $cache = \Phpcmf\Service::L('cache')->get_auth_data($this->codeKey($account), SITE_ID, $this->codeTtl);
        if (!$cache || empty($cache['code']) || $cache['code'] !== $code || (int)$cache['uid'] !== (int)$member['id']) {
            $this->_json(0, '验证码不正确或已过期');
        }

        $rt = \Phpcmf\Service::M('member')->edit_password($member, $password);
        if (!$rt) {
            $this->_json(0, '密码重置失败，请检查密码格式');
        }

        \Phpcmf\Service::L('cache')->del_auth_data($this->codeKey($account));
        $this->_json(1, '密码已重置，请使用新密码登录', ['url' => '/index.php?s=shop&c=login&m=index']);
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

    private function findMember($account, $type)
    {
        $field = $type === 'phone' ? 'phone' : 'email';
        $value = $type === 'phone' ? $account : strtolower($account);
        return \Phpcmf\Service::M()->db->table('member')->where($field, $value)->get()->getRowArray();
    }

    private function ensureEmailCache()
    {
        if (!\Phpcmf\Service::L('cache')->get('email')) {
            \Phpcmf\Service::M('email')->cache(SITE_ID);
        }
    }

    private function codeKey($account)
    {
        return 'shop_password_code_'.md5(strtolower($account));
    }

    private function lockKey($account)
    {
        return 'shop_password_lock_'.md5(strtolower($account));
    }
}
