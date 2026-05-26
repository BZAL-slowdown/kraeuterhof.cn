<?php namespace Phpcmf\Model\Labelling;

class Ueditor extends \Phpcmf\Model
{

    public function show($pix, $field, $option){

            $name = '$'.$pix.$field['fieldname'];

            $html = '';
			$html.= PHP_EOL.'       会员id：{'.$name.'}';
			$html.= PHP_EOL.'       会员头像：{dr_avatar('.$name.')}';

			$html.= PHP_EOL.'       调用会员其他字段，先初始化：{php $user=dr_member_info('.$name.');}';
			$html.= PHP_EOL;
			$html.= PHP_EOL.'       会员name：{$user.name}';
			$html.= PHP_EOL.'       会员username：{$user.username}';
			$html.= PHP_EOL.'       会员phone：{$user.phone}';
			$html.= PHP_EOL.'       会员email：{$user.email}';

            return $html;
    }
}


