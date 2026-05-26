<?php namespace Phpcmf\Model\Labelling;

class Linkages extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

            $name = '$'.$pix.$field['fieldname'];
            $id = $field['id'];

            $html = '';
            $html.= PHP_EOL.'           {loop '.$name.' $v}';
            $html.= PHP_EOL.'           <p>';
            $html.= PHP_EOL.'               联动菜单名称：{dr_linkage(\''.$name.'\', $v, 0, \'name\')}';
            $html.= PHP_EOL.'               联动菜单顶级的名称：{dr_linkage(\''.$name.'\', $v, 1, \'name\')}';
            $html.= PHP_EOL.'               面包屑导航：{dr_linkagepos(\''.$name.'\', $v, \' - \')}';
            $html.= PHP_EOL.'           </p>';
            $html.= PHP_EOL.'           {/loop}';

            return $html;
    }
}


