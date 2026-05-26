<?php namespace Phpcmf\Model\Labelling;

class Textarea extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

            $name = '$'.$pix.$field['fieldname'];

            $html = '';
            $html.= PHP_EOL.'       默认输出：{'.$name.'}';
            $html.= PHP_EOL.'       截取10个字输出：{dr_strcut('.$name.', 10, \'...\')}';
            $html.= PHP_EOL.'       换行显示：{nl2br('.$name.')}';

            return $html;
    }
}