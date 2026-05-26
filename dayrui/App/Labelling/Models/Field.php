<?php namespace Phpcmf\Model\Labelling;

// 模型类

class Field extends \Phpcmf\Model
{

    public function show($pix,$field,$option){


            $html='';
            $folder_list = [];
            $this->find_files(APPSPATH.'/Labelling/Models/',$folder_list);

            if(!$field) return '请选择字段';

            if(in_array(strtolower($field['fieldtype']),$folder_list)){
                $html .= \Phpcmf\Service::M($field['fieldtype'], APP_DIR)->show($pix,$field,$option);
                $html.= PHP_EOL;
                $html.= PHP_EOL;
            }else{
                $html.= $field['fieldtype'].'非官方字段或者不支持输出的字段';
            }
            

            return $html;
    }


        /**
         * @param $dir   要查找的文件路径
         * @param $dir_array    存储文件名的数组
         */
        function find_files($dir, &$dir_array)
        {
            // 读取当前目录下的所有文件和目录（不包含子目录下文件）
            $files = scandir($dir);
         
            if (is_array($files)) {
                foreach ($files as $val) {
                    // 跳过. 和 ..
                    if ($val == '.' || $val == '..')
                        continue;
         
                    // 判断是否是目录
                    if (is_dir($dir . '/' . $val)) {
                        // 将当前目录添加进数组
                        $dir_array[] = $val;
                        // 递归继续往下寻找
                        find_files($dir . '/' . $val, $dir_array);
                    } else {
                        // 不是目录也需要将当前文件添加进数组
                        $dir_array[] = strtolower(str_replace('.php','',$val));
                    }
                }
            }
        }
}



