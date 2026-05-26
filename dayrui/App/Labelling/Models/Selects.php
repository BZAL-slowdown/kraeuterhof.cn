<?php namespace Phpcmf\Model\Labelling;

class Selects extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

            $name = '$'.$pix.$field['fieldname'];
            $id = $field['id'];

            $html = '';
            $html.= PHP_EOL.'       选择值：;';
            $html.= PHP_EOL.'       	{loop '.$name.' $v}';
            $html.= PHP_EOL.'       		{$v}';
            $html.= PHP_EOL.'       	{/loop}';
            $html.= PHP_EOL.'       选择的名称：';
            $html.= PHP_EOL.'           {php $field = dr_field_options('.$id.');}';

            $html.= PHP_EOL.'           	{loop $field $v $name}';
            $html.= PHP_EOL.'           		{if in_array($v,'.$name.')}';
            $html.= PHP_EOL.'           			{$name}';
            $html.= PHP_EOL.'           		{/if}';
            $html.= PHP_EOL.'           	{/loop}';

            return $html;
    }
}


