<?php namespace Phpcmf\Model\Labelling;

class Catids extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

            $name = '$'.$pix.$field['fieldname'];

            $html = '';
	        $html.= PHP_EOL.'       {loop '.$name.' $v}';
	        $html.= PHP_EOL.'       <p>';
	        $html.= PHP_EOL.'           栏目名称：{dr_cat_value(\'模块目录\', $v, \'name\')}';
	        $html.= PHP_EOL.'           栏目地址：{dr_cat_value(\'模块目录\', $v, \'url\')}';
	        $html.= PHP_EOL.'       </p>';
	        $html.= PHP_EOL.'       {/loop}';

            return $html;
    }
}


