<?php namespace Phpcmf\Model\Labelling;

class Catid extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

            $name = '$'.$pix.$field['fieldname'];

            $html = '';

	        $html.= PHP_EOL.'			栏目名称：{dr_cat_value(\'模块目录\', '.$name.', \'name\')}';
	        $html.= PHP_EOL.'			栏目地址：{dr_cat_value(\'模块目录\', '.$name.', \'url\')}';
	        $html.= PHP_EOL;
	        $html.= PHP_EOL.'			其它参考：单独调用，栏目菜单的写法';


            return $html;
    }
}


