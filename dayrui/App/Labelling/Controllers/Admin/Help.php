<?php namespace Phpcmf\Controllers\Admin;

class Help extends \Phpcmf\App
{


    public function __construct(...$params)
    {
        parent::__construct(...$params);

        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '手册直达' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-tag'],
                ]
            ),
        ]);
    }


    public function index() {


        \Phpcmf\Service::V()->display('help.html');
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

                    if ($post['site']) {
                        $html.= ' site='.intval($post['site']);
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

                    if ($post['catid']) {

                        if (!$post['catids']) {
                            $msg_id = 0;
                            $html = '栏目id不能为空，或者请取消指定';
                            $data = '栏目id不能为空，或者请取消指定';
                            break;
                        }else{
                            $html.= ' catid='.$post['catids'];
                        }

                    }else{
                        $html.= ' catid=$catid';
                    }

                    if ($post['num']) {
                        $html.= ' num='.intval($post['num']);
                    }

                    if ($post['page']==1) {
                        $html.= ' page='.$post['page'];

                        if ($post['pagesize']) {
                            $html.= ' pagesize='.$post['pagesize'];
                        }
                    }

                    if ($post['sbpage']>0) {
                        $html.= ' sbpage='.$post['sbpage'];
                    }


                    if ($post['cache']) {
                        $html.= ' cache='.intval($post['cache']);
                    }




                    if ($post['return']) {
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
                        $html.= PHP_EOL.'显示分页代码：{pages}';
                    }

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
                $html.= PHP_EOL.'';
                $html.= PHP_EOL.'更多惊喜，左侧的 “字段输出” 按钮';

                $msg_id = 1;
                break;

        }

        $this->_json($msg_id, $html, $data);

    }

}
