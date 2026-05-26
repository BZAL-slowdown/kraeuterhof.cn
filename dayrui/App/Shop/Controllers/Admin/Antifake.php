<?php namespace Phpcmf\Controllers\Admin;

class Antifake extends \Phpcmf\App
{
    private $catid = 17;
    private $maxRows = 20000;

    public function index()
    {
        $result = null;
        if (IS_POST) {
            $result = $this->import();
        }

        $db = \Phpcmf\Service::M();
        $table = $db->dbprefix('1_fwzb');
        $recent = $db->db->table($table)
            ->select('id,title,scsj,shengchandi,tishi,updatetime')
            ->where('catid', $this->catid)
            ->orderBy('id DESC')
            ->limit(20)
            ->get()
            ->getResultArray();
        $total = $db->db->table($table)->where('catid', $this->catid)->countAllResults();

        \Phpcmf\Service::V()->assign([
            'result' => $result,
            'recent' => $recent,
            'total' => $total,
            'catid' => $this->catid,
            'sample_url' => dr_url('shop/antifake/sample'),
        ]);
        \Phpcmf\Service::V()->display('antifake_index.html');
    }

    public function sample()
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="antifake_codes_sample.csv"');
        echo "\xEF\xBB\xBF";
        echo "code,production_date,origin,message\n";
        echo "A1234567890123,2026-05-13,广州,该防伪码有效，为正品\n";
        echo "B12345678901234567,2026-05-13,广州,该防伪码有效，为正品\n";
        exit;
    }

    private function import()
    {
        $file = isset($_FILES['codes_file']) ? $_FILES['codes_file'] : null;
        if (!$file || empty($file['tmp_name'])) {
            return $this->result(0, '请先选择 CSV 或 TXT 文件');
        }
        if (!empty($file['error'])) {
            return $this->result(0, '文件上传失败，错误码：'.$file['error']);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'], true)) {
            return $this->result(0, '仅支持 CSV/TXT 文件；Excel 请另存为 CSV 后再导入');
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            return $this->result(0, '无法读取上传文件');
        }

        $db = \Phpcmf\Service::M();
        $mainTable = $db->dbprefix('1_fwzb');
        $dataTable = $db->dbprefix('1_fwzb_data_0');
        $indexTable = $db->dbprefix('1_fwzb_index');
        $uid = (int)$this->uid ?: 1;
        $ip = \Phpcmf\Service::L('input')->ip_address();
        $now = time();

        $created = 0;
        $duplicated = 0;
        $invalid = 0;
        $readRows = 0;
        $seen = [];
        $errors = [];
        $hasHeader = false;

        $db->db->transStart();
        while (($row = fgetcsv($handle)) !== false) {
            $readRows++;
            if ($readRows > $this->maxRows) {
                $errors[] = '最多支持一次导入 '.$this->maxRows.' 行，后续行已跳过';
                break;
            }

            $row = $this->normalizeRow($row);
            if (!$row || count(array_filter($row, 'strlen')) === 0) {
                continue;
            }

            if (!$hasHeader && $this->isHeader($row)) {
                $hasHeader = true;
                continue;
            }
            $hasHeader = true;

            $code = $this->cleanCode($row[0] ?? '');
            if (!$this->isValidCode($code)) {
                $invalid++;
                if (count($errors) < 10) {
                    $errors[] = '第 '.$readRows.' 行防伪码格式不正确';
                }
                continue;
            }

            $key = strtolower($code);
            if (isset($seen[$key])) {
                $duplicated++;
                continue;
            }
            $seen[$key] = true;

            $exists = $db->db->table($mainTable)
                ->where('catid', $this->catid)
                ->where('title', $code)
                ->countAllResults();
            if ($exists) {
                $duplicated++;
                continue;
            }

            $productionTime = $this->parseTime($row[1] ?? '');
            $origin = trim((string)($row[2] ?? ''));
            $message = trim((string)($row[3] ?? ''));
            if ($message === '') {
                $message = '该防伪码有效，为正品';
            }

            $db->db->table($mainTable)->insert([
                'catid' => $this->catid,
                'title' => $code,
                'thumb' => null,
                'keywords' => null,
                'description' => null,
                'hits' => 0,
                'uid' => $uid,
                'author' => $this->member['username'] ?? '',
                'status' => 9,
                'url' => '',
                'link_id' => 0,
                'tableid' => 0,
                'inputip' => $ip,
                'inputtime' => $now,
                'updatetime' => $now,
                'displayorder' => 0,
                'scsj' => $productionTime,
                'shengchandi' => $origin,
                'tishi' => $message,
            ]);
            $id = (int)$db->db->insertID();
            if (!$id) {
                $invalid++;
                if (count($errors) < 10) {
                    $errors[] = '第 '.$readRows.' 行写入失败';
                }
                continue;
            }

            $db->db->table($mainTable)->where('id', $id)->update([
                'url' => '/index.php?c=show&id='.$id,
            ]);
            $db->db->table($dataTable)->insert([
                'id' => $id,
                'uid' => $uid,
                'catid' => $this->catid,
                'content' => null,
            ]);
            $db->db->table($indexTable)->insert([
                'id' => $id,
                'uid' => $uid,
                'catid' => $this->catid,
                'status' => 9,
                'inputtime' => $now,
            ]);
            $created++;
        }
        fclose($handle);
        $db->db->transComplete();

        if ($db->db->transStatus() === false) {
            return $this->result(0, '数据库写入失败，已回滚本次导入');
        }

        return $this->result(1, '导入完成', [
            'created' => $created,
            'duplicated' => $duplicated,
            'invalid' => $invalid,
            'read_rows' => $readRows,
            'errors' => $errors,
        ]);
    }

    private function normalizeRow($row)
    {
        foreach ($row as $i => $value) {
            $value = (string)$value;
            if ($i === 0) {
                $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
            }
            if (function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8')) {
                $value = mb_convert_encoding($value, 'UTF-8', 'GBK,GB2312,BIG5,UTF-8');
            }
            $row[$i] = trim($value);
        }
        return $row;
    }

    private function isHeader($row)
    {
        $first = strtolower(trim((string)($row[0] ?? '')));
        return in_array($first, ['code', '防伪码', 'fangweima', 'title'], true);
    }

    private function cleanCode($code)
    {
        $code = trim((string)$code);
        $code = preg_replace('/\s+/', '', $code);
        return preg_replace('/[^\da-zA-Z]/', '', $code);
    }

    private function isValidCode($code)
    {
        return $code !== '' && preg_match('/^[A-Za-z0-9]{6,64}$/', $code);
    }

    private function parseTime($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return 0;
        }
        if (ctype_digit($value) && strlen($value) >= 10) {
            return (int)$value;
        }
        $time = strtotime($value);
        return $time ? $time : 0;
    }

    private function result($code, $msg, $data = [])
    {
        return [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];
    }
}
