<?php namespace Phpcmf\Model\Labelling;

class Ftable extends \Phpcmf\Model
{

    public function show($pix, $field, $option){


            $name = '$'.$pix.$field['fieldname'];
            $id = $field['id'];

            $field['setting'] = dr_string2array($field['setting']);

            $html = '';
            $html.= PHP_EOL.'   手动排列：';
            $html.= PHP_EOL.'       {loop '.$name.' $v}';

            if ($field['setting']['option']['field']) {

                foreach ($field['setting']['option']['field'] as $n => $t) {
                    if ($t['type']) {
                        $val = '{$v['.$n.']}';
                        if ($t['type'] == 3) {
                            // 图片
                            $val = '{dr_get_file($v['.$n.'])}';
                        } elseif ($t['type'] == 4) {
                            // 复选
                            $val = '{php echo $v['.$n.'] ? implode(\'、\', $v['.$n.']) : \'\';}';
                        }
                        $ftable.= PHP_EOL.'         '.$t['name'].': '.$val;
                    }
                }
            }

            $html.= $ftable;
            $html.= PHP_EOL.'       {/loop}';
            $html.= PHP_EOL;

            $html.= PHP_EOL.'   CMS解析：';
            $html.= PHP_EOL.'       默认class写法：{dr_get_ftable('.$id.', '.$name.')}';
            $html.= PHP_EOL.'       自定义table的class写法：{dr_get_ftable('.$id.', '.$name.', \'mytableclass\')}';
            $html.= PHP_EOL.'       mytableclass就是给表格加class，解析为：table calss="mytableclass"';

            return $html;
    }
}