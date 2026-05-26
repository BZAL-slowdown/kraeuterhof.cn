<?php namespace Phpcmf\Model\Labelling;

class Text extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

            $name = '$'.$pix.$field['fieldname'];

			$html = '';
            $html.= PHP_EOL.'       标准输出：{'.$name.'}';
            $html.= PHP_EOL.'       截取输出：{dr_strcut('.$name.', 10, \'...\')}';

	        return $html;
    }
}