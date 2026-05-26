<?php namespace Phpcmf\Model\Labelling;

class Select extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

            $name = '$'.$pix.$field['fieldname'];
            $id = $field['id'];

            $html = '';
            $html.= PHP_EOL.'       选择值：{'.$name.'}';
            $html.= PHP_EOL.'       选择的名称：';
            $html.= PHP_EOL.'           {php $field = dr_field_options('.$id.');}';
            $html.= PHP_EOL.'           {$field['.$name.']}';

            return $html;
    }
}


