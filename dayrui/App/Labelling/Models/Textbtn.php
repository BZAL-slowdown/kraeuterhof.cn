<?php namespace Phpcmf\Model\Labelling;

class Textbtn extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

            $name = '$'.$pix.$field['fieldname'];

			$html = '';
            $html.= PHP_EOL.'       标准输出：{'.$name.'}';

	        return $html;
    }
}