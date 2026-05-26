<?php namespace Phpcmf\Model\Labelling;

class Property extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

            $name = '$'.$pix.$field['fieldname'];
            $id = $field['id'];

            $html = '';
            $html.= PHP_EOL.'              {loop '.$name.' $i $c}';
            $html.= PHP_EOL.'              <p>{$c.name}：{$c.value}</p>';
            $html.= PHP_EOL.'              {/loop}';

            return $html;
    }
}