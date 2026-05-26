<?php namespace Phpcmf\Controllers\Admin;

class Profile extends \Phpcmf\App
{
    public function index()
    {
        $this->ensureTables();

        $db = \Phpcmf\Service::M();
        $input = \Phpcmf\Service::L('input');
        $keyword = dr_safe_replace((string)$input->get('keyword'));

        $profileTable = $db->dbprefix('shop_profile');
        $addressTable = $db->dbprefix('shop_address');

        $profiles = [];
        if ($db->is_table_exists($profileTable)) {
            $builder = $db->db->table($profileTable);
            if ($keyword) {
                $builder->groupStart()
                    ->like('uid', $keyword)
                    ->orLike('display_name', $keyword)
                    ->groupEnd();
            }
            $profiles = $builder->orderBy('id DESC')->limit(100)->get()->getResultArray();
        }

        $addresses = [];
        if ($db->is_table_exists($addressTable)) {
            $rows = $db->db->table($addressTable)->orderBy('id DESC')->limit(500)->get()->getResultArray();
            foreach ($rows as $row) {
                if ($keyword && stripos((string)$row['uid'].' '.$row['buyer_name'].' '.$row['buyer_phone'].' '.$row['buyer_address'], $keyword) === false) {
                    continue;
                }
                if (!isset($addresses[$row['uid']])) {
                    $addresses[$row['uid']] = $row;
                }
            }
        }

        $uids = [];
        foreach ($profiles as $row) {
            $uids[(int)$row['uid']] = 1;
        }
        foreach ($addresses as $uid => $row) {
            $uids[(int)$uid] = 1;
        }

        $members = [];
        $memberTable = $db->dbprefix('member');
        if ($uids && $db->is_table_exists($memberTable)) {
            $memberRows = $db->db->table($memberTable)
                ->whereIn('id', array_keys($uids))
                ->get()
                ->getResultArray();
            foreach ($memberRows as $row) {
                $members[(int)$row['id']] = $row;
            }
        }

        $rows = [];
        $seen = [];
        foreach ($profiles as $row) {
            $uid = (int)$row['uid'];
            $seen[$uid] = 1;
            $row['member_username'] = isset($members[$uid]['username']) ? $members[$uid]['username'] : '';
            $row['address'] = isset($addresses[$uid]) ? $addresses[$uid] : [];
            $rows[] = $row;
        }
        foreach ($addresses as $uid => $address) {
            $uid = (int)$uid;
            if (isset($seen[$uid])) {
                continue;
            }
            $rows[] = [
                'uid' => $uid,
                'avatar' => '',
                'display_name' => '',
                'member_username' => isset($members[$uid]['username']) ? $members[$uid]['username'] : '',
                'address' => $address,
                'updated_at' => isset($address['updated_at']) ? $address['updated_at'] : 0,
            ];
        }

        \Phpcmf\Service::V()->assign([
            'profiles' => $rows,
            'keyword' => $keyword,
        ]);
        \Phpcmf\Service::V()->display('profile_index.html');
    }

    private function ensureTables()
    {
        $db = \Phpcmf\Service::M();
        $profileTable = $db->dbprefix('shop_profile');
        if (!$db->is_table_exists($profileTable)) {
            $db->query("CREATE TABLE IF NOT EXISTS `{$profileTable}` (
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
}
