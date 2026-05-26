<?php namespace Phpcmf\Model\Labelling;

class File extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

            $name = '$'.$pix.$field['fieldname'];

			$html = '';
	        $html.= PHP_EOL.'       文件地址：{dr_get_file('.$name.')}';
	        $html.= PHP_EOL.'       下载地址：{dr_down_file('.$name.')}';
	        $html.= PHP_EOL.'       缩略图：{dr_thumb('.$name.', 100, 100)}';
	        $html.= PHP_EOL.'       缩略图带水印：{dr_thumb('.$name.', 100, 100, 1)}';
	        $html.= PHP_EOL.'';
	        $html.= PHP_EOL.'       强制高宽度：{dr_thumb('.$name.', 100, 100, 0)}';
	        $html.= PHP_EOL.'       高度自适应：{dr_thumb('.$name.', 100, 100, 0, \'width\')}';
	        $html.= PHP_EOL.'       宽度自适应：{dr_thumb('.$name.', 100, 100, 0, \'height\')}';
	        $html.= PHP_EOL.'       中间剪切高宽固定：{dr_thumb('.$name.', 100, 100, 0, \'crop\')}';

	        return $html;
    }
}