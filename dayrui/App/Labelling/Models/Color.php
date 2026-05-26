<?php namespace Phpcmf\Model\Labelling;

class Color extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

            $name = '$'.$pix.$field['fieldname'];

            $html = '';
            $html.= '{'.$name.'}';

            return $html;
    }
}