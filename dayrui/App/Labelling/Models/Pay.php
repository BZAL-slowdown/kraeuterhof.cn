<?php namespace Phpcmf\Model\Labelling;

class Pay extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

        $name = '$'.$pix.$field['fieldname'];
        
        $html = '';
        $html.= PHP_EOL.'              普通输出：{'.$name.'}';
        $html.= PHP_EOL.'              价格值：{dr_price_value('.$name.')}';

        return $html;
    }
}