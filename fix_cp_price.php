<?php

require __DIR__ . '/config/database.php';

$cfg = $db['default'] ?? null;
if (!$cfg) {
    fwrite(STDERR, "config/database.php 中没有找到数据库配置\n");
    exit(1);
}

$prefix = $cfg['DBPrefix'] ?: 'dr_';
$m = new mysqli($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database']);
if ($m->connect_error) {
    fwrite(STDERR, "数据库连接失败: {$m->connect_error}\n");
    exit(1);
}
$m->set_charset('utf8mb4');

$q = $m->query("SELECT id, dirname, setting FROM {$prefix}module WHERE LOWER(dirname)='cp' LIMIT 1");
$module = $q ? $q->fetch_assoc() : null;
if (!$module) {
    fwrite(STDERR, "没有找到 cp 模块\n");
    exit(1);
}

$moduleId = (int)$module['id'];
$setting = json_decode((string)$module['setting'], true);
if (!is_array($setting)) {
    $setting = [];
}

$sites = $m->query("SELECT id FROM {$prefix}site WHERE disabled=0");
if (!$sites) {
    fwrite(STDERR, "读取站点列表失败: " . $m->error . "\n");
    exit(1);
}

while ($site = $sites->fetch_assoc()) {
    $siteId = (int)$site['id'];
    $table = "{$prefix}{$siteId}_cp";

    $check = $m->query("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='" . $m->real_escape_string($table) . "'");
    $row = $check ? $check->fetch_assoc() : null;
    if (empty($row['c'])) {
        continue;
    }

    $check = $m->query("SHOW COLUMNS FROM `{$table}` LIKE 'price'");
    if ($check && (int)$check->num_rows === 0) {
        if (!$m->query("ALTER TABLE `{$table}` ADD `price` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品价格' AFTER `description`")) {
            fwrite(STDERR, "添加字段失败({$table}): " . $m->error . "\n");
            exit(1);
        }
    }
}

$priceSetting = [
    'option' => [
        'payfile' => 'buy.html',
        'is_finecms' => 0,
        'width' => 150,
    ],
    'validate' => [
        'xss' => 1,
        'required' => 1,
        'formattr' => '',
    ],
];

$priceField = [
    'name' => '价格',
    'fieldname' => 'price',
    'fieldtype' => 'Pay',
    'relatedid' => $moduleId,
    'relatedname' => 'module',
    'isedit' => 1,
    'ismain' => 1,
    'issystem' => 0,
    'ismember' => 1,
    'issearch' => 0,
    'disabled' => 0,
    'setting' => json_encode($priceSetting, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    'displayorder' => 10,
];

$setting['list_field'] = is_array($setting['list_field'] ?? null) ? $setting['list_field'] : [];
$setting['list_field']['price'] = [
    'use' => '1',
    'name' => '价格',
    'width' => '120',
    'func' => 'price',
];

$settingJson = $m->real_escape_string(json_encode($setting, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$check = $m->query("SELECT id FROM {$prefix}field WHERE relatedid={$moduleId} AND relatedname='module' AND fieldname='price' LIMIT 1");
$field = $check ? $check->fetch_assoc() : null;
if ($field) {
    $fieldId = (int)$field['id'];
    $fieldJson = $m->real_escape_string(json_encode(array_merge($priceField, ['id' => $fieldId]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    if (!$m->query("UPDATE {$prefix}field SET name='价格', fieldtype='Pay', relatedid={$moduleId}, relatedname='module', isedit=1, ismain=1, issystem=0, ismember=1, issearch=0, disabled=0, setting='" . $m->real_escape_string($priceField['setting']) . "', displayorder=10 WHERE id={$fieldId}")) {
        fwrite(STDERR, "更新字段失败: " . $m->error . "\n");
        exit(1);
    }
} else {
    $sql = "INSERT INTO {$prefix}field (name, fieldname, fieldtype, relatedid, relatedname, isedit, ismain, issystem, ismember, issearch, disabled, setting, displayorder) VALUES ('价格', 'price', 'Pay', {$moduleId}, 'module', 1, 1, 0, 1, 0, 0, '" . $m->real_escape_string($priceField['setting']) . "', 10)";
    if (!$m->query($sql)) {
        fwrite(STDERR, "插入字段失败: " . $m->error . "\n");
        exit(1);
    }
}

if (!$m->query("UPDATE {$prefix}module SET setting='" . $settingJson . "' WHERE id={$moduleId}")) {
    fwrite(STDERR, "更新模块配置失败: " . $m->error . "\n");
    exit(1);
}

echo "价格字段已写入，请回后台执行一次“系统缓存/更新缓存”。\n";
