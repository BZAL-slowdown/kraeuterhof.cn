<?php namespace Phpcmf\Model\Labelling;

class Date extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

            $name = '$'.$pix.$field['fieldname'];

            $html = '';
            $html.= PHP_EOL.'       默认输出：{'.$name.'}';

            if(strpos($name, '.')){
                $name = str_replace('.', '._', $name);
            }else{
                $name = str_replace('$', '$_', $name);
            }
            $html.= PHP_EOL.'       自定义时间：{dr_date('.$name.', \'Y-m-d\')}';
            $html.= PHP_EOL.'       友好的时间：{dr_fdate('.$name.')}';

            return $html;
    }
}