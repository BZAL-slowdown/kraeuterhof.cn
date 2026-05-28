<?php

/**
 * Add product coupon and SKU price fields used by the Shop H5 checkout.
 *
 * Run from the project root on the production server:
 * php scripts/ensure_shop_product_price_fields.php
 */

error_reporting(E_ALL);

$root = dirname(__DIR__);
$databaseFile = $root.'/config/database.php';
if (!is_file($databaseFile)) {
    fwrite(STDERR, "Missing config/database.php\n");
    exit(1);
}

$db = [];
require $databaseFile;
$config = $db['default'] ?? [];
foreach (['hostname', 'username', 'database'] as $key) {
    if (empty($config[$key]) || strpos((string)$config[$key], 'your_database_') !== false) {
        fwrite(STDERR, "Database config is not ready: {$key}\n");
        exit(1);
    }
}

$prefix = $config['DBPrefix'] ?? 'dr_';
$port = 3306;
$host = (string)$config['hostname'];
if (strpos($host, ':') !== false) {
    [$host, $portText] = explode(':', $host, 2);
    $port = (int)$portText ?: 3306;
}

$mysqli = new mysqli($host, (string)$config['username'], (string)($config['password'] ?? ''), (string)$config['database'], $port);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "Connect failed: ".$mysqli->connect_error."\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

function q(mysqli $mysqli, string $name): string
{
    return '`'.str_replace('`', '``', $name).'`';
}

function table_exists(mysqli $mysqli, string $table): bool
{
    $table = $mysqli->real_escape_string($table);
    $rs = $mysqli->query("SHOW TABLES LIKE '{$table}'");
    return $rs && $rs->num_rows > 0;
}

function column_exists(mysqli $mysqli, string $table, string $column): bool
{
    $column = $mysqli->real_escape_string($column);
    $rs = $mysqli->query('SHOW COLUMNS FROM '.q($mysqli, $table)." LIKE '{$column}'");
    return $rs && $rs->num_rows > 0;
}

function exec_sql(mysqli $mysqli, string $sql): void
{
    if (!$mysqli->query($sql)) {
        fwrite(STDERR, "SQL failed: ".$mysqli->error."\n".$sql."\n");
        exit(1);
    }
}

function decode_setting(?string $value): array
{
    if (!$value) {
        return [];
    }

    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : [];
}

function encode_setting(array $value): string
{
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function clear_cache_dir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile() || $file->isLink()) {
            @unlink($file->getPathname());
        } elseif ($file->isDir()) {
            @rmdir($file->getPathname());
        }
    }
}

$productTable = $prefix.'1_cp';
$moduleTable = $prefix.'module';
$fieldTable = $prefix.'field';

if (!table_exists($mysqli, $productTable)) {
    fwrite(STDERR, "Missing product table: {$productTable}\n");
    exit(1);
}
if (!table_exists($mysqli, $moduleTable) || !table_exists($mysqli, $fieldTable)) {
    fwrite(STDERR, "Missing CMS module/field table\n");
    exit(1);
}

$columns = [
    'coupon_before_price' => "DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT 'coupon before price'",
    'coupon_after_price' => "DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT 'coupon after price'",
    'sku_price_text' => "TEXT NULL COMMENT 'sku price config'",
];

$after = column_exists($mysqli, $productTable, 'price') ? 'price' : 'description';
foreach ($columns as $name => $definition) {
    if (!column_exists($mysqli, $productTable, $name)) {
        exec_sql($mysqli, 'ALTER TABLE '.q($mysqli, $productTable).' ADD '.q($mysqli, $name).' '.$definition.' AFTER '.q($mysqli, $after));
        echo "Added column {$name}\n";
    } else {
        echo "Column exists {$name}\n";
    }
    $after = $name;
}

$moduleRs = $mysqli->query("SELECT id FROM ".q($mysqli, $moduleTable)." WHERE dirname='cp' OR dirname='Cp' ORDER BY id ASC LIMIT 1");
$module = $moduleRs ? $moduleRs->fetch_assoc() : null;
if (!$module) {
    fwrite(STDERR, "Missing cp module\n");
    exit(1);
}
$moduleId = (int)$module['id'];

$fieldSettings = [
    'coupon_before_price' => [
        'name' => '优惠前价格',
        'fieldtype' => 'Text',
        'setting' => [
            'option' => ['width' => 150, 'fieldtype' => 'DECIMAL', 'fieldlength' => '10,2', 'value' => ''],
            'validate' => ['xss' => 1, 'required' => 0, 'formattr' => 'placeholder="例如：81.20"'],
        ],
        'displayorder' => 11,
        'disabled' => 1,
    ],
    'coupon_after_price' => [
        'name' => '券后价格',
        'fieldtype' => 'Text',
        'setting' => [
            'option' => ['width' => 150, 'fieldtype' => 'DECIMAL', 'fieldlength' => '10,2', 'value' => ''],
            'validate' => ['xss' => 1, 'required' => 0, 'formattr' => 'placeholder="例如：69.00"'],
        ],
        'displayorder' => 12,
        'disabled' => 1,
    ],
    'sku_price_text' => [
        'name' => '规格价格配置',
        'fieldtype' => 'Textarea',
        'setting' => [
            'option' => ['width' => '80%', 'height' => 120, 'fieldtype' => 'TEXT', 'fieldlength' => '', 'value' => ''],
            'validate' => [
                'xss' => 1,
                'required' => 0,
                'formattr' => 'placeholder="每行一个规格：100ml|81.20|69.00"',
                'tips' => '每行一个规格：规格名称|优惠前价格|券后价格，例如 100ml|81.20|69.00',
            ],
        ],
        'displayorder' => 13,
        'disabled' => 0,
    ],
];

foreach ($fieldSettings as $fieldname => $field) {
    $name = $mysqli->real_escape_string($field['name']);
    $type = $mysqli->real_escape_string($field['fieldtype']);
    $setting = $mysqli->real_escape_string(encode_setting($field['setting']));
    $fieldnameSql = $mysqli->real_escape_string($fieldname);
    $exists = $mysqli->query("SELECT id FROM ".q($mysqli, $fieldTable)." WHERE relatedname='module' AND relatedid={$moduleId} AND fieldname='{$fieldnameSql}' LIMIT 1");
    if ($exists && $exists->num_rows) {
        $row = $exists->fetch_assoc();
        exec_sql($mysqli, "UPDATE ".q($mysqli, $fieldTable)." SET name='{$name}', fieldtype='{$type}', isedit=1, ismain=1, ismember=1, issystem=0, issearch=0, disabled=".(int)$field['disabled'].", setting='{$setting}', displayorder=".(int)$field['displayorder']." WHERE id=".(int)$row['id']);
        echo "Updated field {$fieldname}\n";
    } else {
        exec_sql($mysqli, "INSERT INTO ".q($mysqli, $fieldTable)." (name, fieldname, fieldtype, relatedid, relatedname, isedit, ismain, ismember, issystem, issearch, disabled, setting, displayorder) VALUES ('{$name}', '{$fieldnameSql}', '{$type}', {$moduleId}, 'module', 1, 1, 1, 0, 0, ".(int)$field['disabled'].", '{$setting}', ".(int)$field['displayorder'].")");
        echo "Inserted field {$fieldname}\n";
    }
}

// Some XunRui category settings store a field whitelist. If present, extend it.
foreach ([$prefix.'1_share_category', $prefix.'1_cp_category'] as $categoryTable) {
    if (!table_exists($mysqli, $categoryTable)) {
        continue;
    }

    $rs = $mysqli->query("SELECT id, setting FROM ".q($mysqli, $categoryTable)." WHERE module='cp' OR mid='cp' OR dirname='cp'");
    if (!$rs) {
        $rs = $mysqli->query("SELECT id, setting FROM ".q($mysqli, $categoryTable));
    }

    $changed = 0;
    while ($row = $rs ? $rs->fetch_assoc() : null) {
        $setting = decode_setting($row['setting'] ?? '');
        if (!$setting || !isset($setting['module_field']) || !is_array($setting['module_field'])) {
            continue;
        }

        foreach (array_keys($fieldSettings) as $fieldname) {
            $setting['module_field'][$fieldname] = 1;
        }

        $encoded = $mysqli->real_escape_string(encode_setting($setting));
        exec_sql($mysqli, "UPDATE ".q($mysqli, $categoryTable)." SET setting='{$encoded}' WHERE id=".(int)$row['id']);
        $changed++;
    }

    echo "Updated category field whitelist {$categoryTable}: {$changed}\n";
}

foreach (['template', 'table', 'config'] as $cacheDir) {
    clear_cache_dir($root.'/cache/'.$cacheDir);
    echo "Cleared cache/{$cacheDir}\n";
}

$dataCachePatterns = [
    'table-*.cache',
    'table-field.cache',
];
foreach ($dataCachePatterns as $pattern) {
    foreach (glob($root.'/cache/data/'.$pattern) ?: [] as $cacheFile) {
        @unlink($cacheFile);
        echo 'Removed cache/data/'.basename($cacheFile)."\n";
    }
}
echo "Skipped cache/data/module-*.cache to avoid breaking app routing. Use admin System Cache > Update Cache when module cache refresh is needed.\n";

echo "\nVerification:\n";
foreach (array_keys($fieldSettings) as $fieldname) {
    $columnOk = column_exists($mysqli, $productTable, $fieldname) ? 'COLUMN OK' : 'NO COLUMN';
    $fieldnameSql = $mysqli->real_escape_string($fieldname);
    $fieldOk = 'NO FIELD';
    $rs = $mysqli->query("SELECT id, name, disabled FROM ".q($mysqli, $fieldTable)." WHERE relatedname='module' AND relatedid={$moduleId} AND fieldname='{$fieldnameSql}' LIMIT 1");
    if ($rs && $rs->num_rows) {
        $row = $rs->fetch_assoc();
        $fieldOk = 'FIELD OK #'.$row['id'].' '.$row['name'].' disabled='.$row['disabled'];
    }
    echo "{$fieldname}: {$columnOk}; {$fieldOk}\n";
}

echo "\nDone. Open admin product edit page, then use System Cache > Update Cache if the fields are still not visible.\n";
