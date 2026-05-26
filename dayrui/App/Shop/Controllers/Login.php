<?php namespace Phpcmf\Controllers;

class Login extends \Phpcmf\App
{
    public function index()
    {
        $back = $this->backUrl((string)\Phpcmf\Service::L('input')->get('back'));
        \Phpcmf\Service::V()->assign([
            'meta_title' => '会员登录',
            'login_url' => '/index.php?s=shop&c=login&m=submit',
            'register_url' => '/index.php?s=shop&c=register&m=index',
            'find_url' => '/index.php?s=shop&c=password&m=find',
            'home_url' => '/',
            'back_url' => $back,
        ]);
        \Phpcmf\Service::V()->display('login_index.html');
    }

    public function submit()
    {
        if (!IS_POST) {
            $this->_json(0, '非法请求');
        }

        $input = \Phpcmf\Service::L('input');
        $account = trim((string)$input->post('account'));
        $password = (string)$input->post('password');
        $remember = (int)$input->post('remember');
        $back = $this->backUrl((string)$input->post('back'));

        if ($account === '' || $password === '') {
            $this->_json(0, '请填写账号和密码');
        }

        $member = $this->findMember($account);
        if (!$member || !$this->checkPassword($member, $password)) {
            $this->_json(0, '账号或密码不正确');
        }

        $data = \Phpcmf\Service::M()->db->table('member_data')->where('id', (int)$member['id'])->get()->getRowArray();
        if ($data && !empty($data['is_lock'])) {
            $this->_json(0, '账号已被锁定');
        }

        $member['uid'] = (int)$member['id'];
        \Phpcmf\Service::M('member')->save_cookie($member, $remember);
        $this->_json(1, '登录成功', ['url' => $back]);
    }

    private function findMember($account)
    {
        $db = \Phpcmf\Service::M()->db->table('member');
        if (filter_var($account, FILTER_VALIDATE_EMAIL)) {
            return $db->where('email', strtolower($account))->get()->getRowArray();
        }
        if (preg_match('/^1[3-9]\d{9}$/', $account)) {
            return $db->where('phone', $account)->get()->getRowArray();
        }
        return $db->where('username', $account)->get()->getRowArray();
    }

    private function checkPassword($member, $password)
    {
        $salt = (string)($member['salt'] ?? '');
        $hash = md5(md5($password).$salt.md5($password));
        return hash_equals((string)($member['password'] ?? ''), $hash);
    }

    private function backUrl($url)
    {
        $url = trim($url);
        if ($url) {
            $decoded = urldecode($url);
            if (strpos($decoded, 'http://') === 0 || strpos($decoded, 'https://') === 0) {
                $host = parse_url($decoded, PHP_URL_HOST);
                if ($host && $host === ($_SERVER['HTTP_HOST'] ?? '')) {
                    return $decoded;
                }
            } elseif ($decoded[0] === '/') {
                return $decoded;
            }
        }
        return '/index.php?s=member&app=shop&c=center&m=index';
    }
}
