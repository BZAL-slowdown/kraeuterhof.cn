<?php namespace Phpcmf\Model\Labelling;

class Members extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

        $name = '$'.$pix.$field['fieldname'];
        
        $html = '';
        $html.= PHP_EOL.'              {if '.$name.'}';
        $html.= PHP_EOL.'              {member IN_id='.$name.' return=r}';
        $html.= PHP_EOL.'              {$r.id}';
        $html.= PHP_EOL.'              {$r.username}';
        $html.= PHP_EOL.'              ......';
        $html.= PHP_EOL.'              {/member}';
        $html.= PHP_EOL.'              {else}';
        $html.= PHP_EOL.'              没有关联';
        $html.= PHP_EOL.'              {/if}';

        return $html;
    }
}