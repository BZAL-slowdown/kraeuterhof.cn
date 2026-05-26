<?php namespace Phpcmf\Model\Labelling;

class Baidumap extends \Phpcmf\Model
{

    public function show($pix, $field, $option){
    	
            $name = '$'.$pix.$field['fieldname'];

            $html = '';
            $html.= PHP_EOL.'       {php $mapinfo = \'公司名<br>公司地址<br>联系电话\';}';
            $html.= PHP_EOL.'       调用百度地图：{dr_baidu_map('.$name.', 17, \'100%\', \'400\', SYS_BDMAP_API, \'class\', $mapinfo)}';

            return $html;
    }
}