<?php namespace Phpcmf\Model\Labelling;

class Files extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

            $name = '$'.$pix.$field['fieldname'];

            $html = '';
            $html.= PHP_EOL.'       {loop '.$name.' $i $c}';
            $html.= PHP_EOL.'       序号: {$i}';
            $html.= PHP_EOL.'       标题：{$c.title}';
            $html.= PHP_EOL.'       描述：{$c.description}';
            $html.= PHP_EOL.'       文件原始地址：{dr_get_file($c.file)}';
            $html.= PHP_EOL.'       文件的下载地址：{dr_down_file($c.file)}';
            $html.= PHP_EOL.'       图片缩略图：{dr_thumb($c.file, 200, 200)}';
            $html.= PHP_EOL.'       图片缩略图带水印：{dr_thumb($c.file, 200, 200, 1)}';
            $html.= PHP_EOL.'       {/loop}';

            return $html;
    }
}