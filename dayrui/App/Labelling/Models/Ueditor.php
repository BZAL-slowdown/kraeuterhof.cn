<?php namespace Phpcmf\Model\Labelling;

class Ueditor extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

            $name = '$'.$pix.$field['fieldname'];

            $html = '';
            $html.= PHP_EOL.'       默认输出：{'.$name.'}';
            $html.= PHP_EOL.'       去掉html标签：{dr_clearhtml('.$name.')}';
            $html.= PHP_EOL.'       去掉html标签再截10个字：{dr_strcut(dr_clearhtml('.$name.'), 10, \'...\')}';

            $html.= PHP_EOL.'       读取内容字段中的全部图片';
            $html.= PHP_EOL.'       {php $imgs = dr_get_content_img('.$name.');}';
            $html.= PHP_EOL.'       {loop $imgs $img}';
            $html.= PHP_EOL.'       {$img}';
            $html.= PHP_EOL.'       {/loop}';
            $html.= PHP_EOL.'       图片数量：{count($imgs)}';

            return $html;
    }
}