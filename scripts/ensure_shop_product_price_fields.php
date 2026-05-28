<?php

/**
 * Add product price fields used by the Shop H5 checkout.
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
    'coupon_before_price' => "DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '优惠前价格'",
    'coupon_after_price' => "DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '券后价格'",
    'sku_price_text' => "TEXT NULL COMMENT '规格价格配置：规格|优惠前价|券后价'",
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
            'validate' => ['xss' => 1, 'required' => 0, 'formattr' => ''],
        ],
        'displayorder' => 11,
    ],
    'coupon_after_price' => [
        'name' => '券后价格',
        'fieldtype' => 'Text',
        'setting' => [
            'option' => ['width' => 150, 'fieldtype' => 'DECIMAL', 'fieldlength' => '10,2', 'value' => ''],
            'validate' => ['xss' => 1, 'required' => 0, 'formattr' => ''],
        ],
        'displayorder' => 12,
    ],
    'sku_price_text' => [
        'name' => '规格价格配置',
        'fieldtype' => 'Textarea',
        'setting' => [
            'option' => ['width' => '80%', 'height' => 120, 'fieldtype' => 'TEXT', 'fieldlength' => '', 'value' => ''],
            'validate' => ['xss' => 1, 'required' => 0, 'formattr' => 'placeholder="每行一个规格：100ML|81.20|69.00"'],
        ],
        'displayorder' => 13,
    ],
];

foreach ($fieldSettings as $fieldname => $field) {
    $name = $mysqli->real_escape_string($field['name']);
    $type = $mysqli->real_escape_string($field['fieldtype']);
    $setting = $mysqli->real_escape_string(json_encode($field['setting'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $fieldnameSql = $mysqli->real_escape_string($fieldname);
    $exists = $mysqli->query("SELECT id FROM ".q($mysqli, $fieldTable)." WHERE relatedname='module' AND relatedid={$moduleId} AND fieldname='{$fieldnameSql}' LIMIT 1");
    if ($exists && $exists->num_rows) {
        $row = $exists->fetch_assoc();
        exec_sql($mysqli, "UPDATE ".q($mysqli, $fieldTable)." SET name='{$name}', fieldtype='{$type}', isedit=1, ismain=1, ismember=1, issystem=0, issearch=0, disabled=0, setting='{$setting}', displayorder=".(int)$field['displayorder']." WHERE id=".(int)$row['id']);
        echo "Updated field {$fieldname}\n";
    } else {
        exec_sql($mysqli, "INSERT INTO ".q($mysqli, $fieldTable)." (name, fieldname, fieldtype, relatedid, relatedname, isedit, ismain, ismember, issystem, issearch, disabled, setting, displayorder) VALUES ('{$name}', '{$fieldnameSql}', '{$type}', {$moduleId}, 'module', 1, 1, 1, 0, 0, 0, '{$setting}', ".(int)$field['displayorder'].")");
        echo "Inserted field {$fieldname}\n";
    }
}

echo "Done. Please clear XunRui caches and template caches.\n";
