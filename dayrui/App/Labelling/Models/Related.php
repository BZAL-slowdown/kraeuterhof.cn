<?php namespace Phpcmf\Model\Labelling;

class Related extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

        $name = '$'.$pix.$field['fieldname'];
        $id = $field['id'];

        $html = '';
        $html.= PHP_EOL.'       {if '.$name.'}';
        $html.= PHP_EOL.'           {module module='.$option.' IN_id='.$name.' num=5 return=r}';
        $html.= PHP_EOL.'               <p><a href="{$r.url}">{$r.title}</a></p>';
        $html.= PHP_EOL.'           {/module}';
        $html.= PHP_EOL.'       {else}';
        $html.= PHP_EOL.'              没有关联';
        $html.= PHP_EOL.'       {/if}';

        return $html;
    }
}