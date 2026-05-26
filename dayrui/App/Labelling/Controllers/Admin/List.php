<?php namespace Phpcmf\Controllers\Admin;

class Module extends \Phpcmf\App
{


    public function __construct(...$params)
    {
        parent::__construct(...$params);

        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '内容循环调用' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/loop', 'fa fa-tag'],
                    '内容单独调用' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-tag'],
                ]
            ),
        ]);
    }


    public function index() {


        \Phpcmf\Service::V()->display('module_index.html');
    }

    public function loop() {


        \Phpcmf\Service::V()->display('module_loop.html');
    }

    public function param() {

        $html = '';
        $post = $_POST;


        if ($post['site']) {
            $site=$post['site'];
        }else{
            $site=1;
        }


        $flag = \Phpcmf\Service::C()->get_cache('module-'.$site.'-'.$post['module'],'setting','flag');
        if($flag){
            $html.= '<option value="0">不选</option>';
            foreach ($flag as $key => $t) {
                    $html.= '<option value="'.$key.'">'.$t['name'].'</option>';  
            }
        }
        $this->_json(1, $html);
    }



    public function labelling() {

        $html = '';
        $post = $_POST;

        switch (intval($_GET['id'])) {

            case 1:
                // 共享栏目循环

                $html.= '{module';
                    if ($post['module']) {
                        $html.= ' module='.$post['module'];
                    }else{

                        if($post['temp']==1){
                            $html.= ' module=MOD_DIR';
                        }else{
                            $msg_id = 0;
                            $html = '其他页面时，必须指定模块';
                            $data = '其他页面时，必须指定模块';
                            break;
                        }
                    }


                    if ($post['field']==1) {
                            if(empty($post['fields'])){
                                $msg_id = 0;
                                $html = '字段不能为空，或者请取消指定';
                                $data = '字段不能为空，或者请取消指定';
                                break;
                            }else{
                            $html.= ' field='.$post['fields'];
                        }
                    }


                    if ($post['flag']>0) {
                        $html.= ' flag='.intval($post['flag']);
                    }

                    if ($post['catid']==10000) {

                        if (!$post['catids']) {
                            $msg_id = 0;
                            $html = '栏目id不能为空，或者请取消指定';
                            $data = '栏目id不能为空，或者请取消指定';
                            break;
                        }else{
                            $html.= ' catid='.$post['catids'];
                        }

                    }elseif(!$post['catid']){
                        $html.= ' catid=$catid';
                    }else{
                        $html.= ' catid='.$post['catid'];
                    }

                    if ($post['num']&&!$post['page']) {
                        $html.= ' num='.intval($post['num']);
                    }

                        $html.= ' order=displayorder,updatetime';

                    if ($post['page']==1) {
                        $html.= ' page='.$post['page'];

                        if ($post['pagesize']&&$post['sbpage']==1) {
                            $html.= ' pagesize='.$post['pagesize'];
                        }
                    }

                    if ($post['sbpage']>0&&$post['page']==1) {
                        $html.= ' sbpage='.$post['sbpage'];
                    }


                    if ($post['cache']) {
                        $html.= ' cache='.intval($post['cache']);
                    }




                    if ($post['return']&&$post['return']!='t') {
                        $t = $post['return'];
                        $key = '{$key_'.$t.'}';
                        $return = ' return='.$post['return'].'}';
                    }else{
                        $t = 't';
                        $key = '{$key}';
                        $return = '}';
                    }
                $html.= $return;


                $html.= PHP_EOL.'<li>'.$key.'<a href="{$'.$t.'.url}" title="{$'.$t.'.title}">{$'.$t.'.title}</a></li>';
                // 自定义字段
                $html.= PHP_EOL.'{/module}';

                    if ($post['page']==1) {
                        $html.= PHP_EOL.'分页：{pages}';
                    }

                $html.= PHP_EOL.'';






                //字段获取
                $diyfiled = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$post['module'],'field');

                // 默认字段
                $diyfiled['catid'] = [
                    'name' => dr_lang('栏目'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Catid',
                    'fieldname' => 'catid',
                ];
                $diyfiled['inputtime'] = [
                    'name' => dr_lang('录入时间'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Date',
                    'fieldname' => 'inputtime',
                ];
                $diyfiled['updatetime'] = [
                    'name' => dr_lang('更新时间'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Date',
                    'fieldname' => 'updatetime',
                ];


                foreach ($diyfiled as $f) {
                        $html.= PHP_EOL.$f['name'].'：';
                        //$data.= '<option value="'.$t['fieldname'].'">'.$t['name'].'（'.$t['fieldname'].'）</option>';  

                        //$html.= PHP_EOL.$t['name']
                        $html.= \Phpcmf\Service::M('Field', APP_DIR)->show($f['fieldtype'],'$'.$t.'.'.$f['fieldname'],$f['id'],$option);
                }




                /*
                $html.= PHP_EOL.'左边菜单 “内容调用” 可以输出字段详细解析方法';
                $html.= PHP_EOL.'';
                $html.= PHP_EOL.'常用函数：';
                $html.= PHP_EOL.'截取：{dr_strcut($'.$t.'.title), 10, \'...\'}';
                $html.= PHP_EOL.'过滤html：{dr_clearhtml($'.$t.'.title)} ';
                $html.= PHP_EOL.'';
                $html.= PHP_EOL.'缩略图：{dr_thumb($'.$t.'.thumb)}';
                $html.= PHP_EOL.'缩略图：{dr_thumb($'.$t.'.thumb, 100, 100)}';
                $html.= PHP_EOL.'缩略图带水印：{dr_thumb($'.$t.'.thumb, 100, 100, 1)}';
                $html.= PHP_EOL.'';
                $html.= PHP_EOL.'文件真实地址：{dr_get_file($'.$t.'.thumb)}';
                $html.= PHP_EOL.'文件下载地址：{dr_down_file($'.$t.'.thumb)}';
                $html.= PHP_EOL.'';
                */
                $html.= PHP_EOL.'更多惊喜，点下方的“字段输出”按钮';

                $msg_id = 1;
                break;

        }

        $this->_json($msg_id, $html, $data);

    }

}
