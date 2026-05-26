<?php namespace Phpcmf\Model\Labelling;

class Score extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

        $name = '$'.$pix.$field['fieldname'];
        $id = $field['id'];


        $html = '';

        $html.= PHP_EOL.'       {if '.$name.'_sku}';
        $html.= PHP_EOL.'       按用户组的值：';
        $html.= PHP_EOL.'       {php $vsku = dr_string2array('.$name.'_sku);}';
        $html.= PHP_EOL.'       {cache name=member_group return=mc}';
        $html.= PHP_EOL.'       <p>用户组【{$mc.name}】: {$vsku[$mc.id]}</p>';
        $html.= PHP_EOL.'       {/cache}';
        $html.= PHP_EOL.'       {else}';
        $html.= PHP_EOL.'       全局值：{'.$name.'}';
        $html.= PHP_EOL.'       {/if}';


        return $html;
    }
}