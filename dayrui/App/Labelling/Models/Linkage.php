<?php namespace Phpcmf\Model\Labelling;

class Linkage extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

            $name = '$'.$pix.$field['fieldname'];
            $id = $field['id'];

            $html = '';
            $html.= PHP_EOL.'           联动菜单名称：';
            $html.= PHP_EOL.'               {dr_linkage(\''.$option.'\', '.$name.', 0, \'name\')}';
            $html.= PHP_EOL.'           联动菜单顶级的名称：';
            $html.= PHP_EOL.'               {dr_linkage(\''.$option.'\', '.$name.', 1, \'name\')}';
            $html.= PHP_EOL.'           面包屑导航：';
            $html.= PHP_EOL.'              {dr_linkagepos(\''.$option.'\', '.$name.', \' - \')}';

            return $html;
    }
}


