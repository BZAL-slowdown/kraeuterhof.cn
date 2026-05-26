<?php namespace Phpcmf\Controllers\Admin;

class Category extends \Phpcmf\App
{
    private $sys_field = [
        'name' => [
            'name' => '名称',
            'ismain' => 1,
            'fieldtype' => 'Text',
            'fieldname' => 'name',
        ],
        'dirname' => [
            'name' => '目录',
            'ismain' => 1,
            'fieldtype' => 'Text',
            'fieldname' => 'dirname',
        ],
        'content' => [
            'name' => '内容',
            'ismain' => 1,
            'fieldtype' => 'Ueditor',
            'fieldname' => 'content',
            'setting' => array(
                'option' => array(
                    'mode' => 1,
                    'height' => 300,
                    'width' => '100%'
                )
            ),
        ],
        'thumb' => [
            'name' => '缩略图',
            'ismain' => 1,
            'fieldtype' => 'File',
            'fieldname' => 'thumb',
            'setting' => array(
                'option' => array(
                    'mode' => 1,
                    'height' => 300,
                    'width' => '100%'
                )
            ),
        ],
        'url' => [
            'name' => '地址',
            'ismain' => 1,
            'fieldtype' => 'Text',
            'fieldname' => 'url',
        ],
        'total' => [
            'name' => '记录数',
            'ismain' => 1,
            'fieldtype' => 'Text',
            'fieldname' => 'total',
        ]
    ];

    public function __construct(...$params)
    {
        parent::__construct(...$params);

        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '栏目循环调用' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/loop', 'fa fa-tag'],
                    '栏目单独调用' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-tag'],
                ]
            ),
        ]);
    }


    public function index() {
        \Phpcmf\Service::V()->display('category_index.html');
    }

    public function loop() {
        \Phpcmf\Service::V()->display('category_loop.html');
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



    public function cat_value() {

                $html = '';
                $data = '';
                $post = $_POST;

                //栏目独立调用
                if ($post['module']=='share') {
                    $share = 'share_';
                }else{
                    $module_name = '"'.$post['module'].'",';
                }


                if ($post['catid']){
                    $msg_id = 1;


                if($post['module'] == 'share'){
                    $cate_file_mod = dr_share_cat_value($post['catid'],'mid');
                }else{
                    $cate_file_mod = $post['module'];
                }



                if(!$cate_file_mod){
                    $filed = $this->sys_field;
                }else{

                    //字段获取
                    $diyfiled = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$cate_file_mod,'field');

            
                    $filed = array_merge($diyfiled,$this->sys_field);

                }
          
                


                $s = $filed[$post['field']];




                $html= '指定栏目（'.$post['field'].'）输出代码：';
                $html.= PHP_EOL.'';

                if($s['fieldtype']=='Text'){
                $html.= PHP_EOL.'       直接调用：';
                $html.= PHP_EOL.'           {dr_'.$share.'cat_value('.$module_name.$post['catid'].',\''.$post['field'].'\')}';
                $html.= PHP_EOL.'';
                }



                $html.= PHP_EOL.'   其他调用方法：';
                $html.= PHP_EOL.'       初始化：<!--初始化 $'.$post['field'].' 之后，下面才可以调用-->';
                $html.= PHP_EOL.'       {php $'.$post['field'].' = dr_'.$share.'cat_value('.$module_name.$post['catid'].',\''.$post['field'].'\');}';
                $html.= PHP_EOL.'';
                $html.= \Phpcmf\Service::M('Field', APP_DIR)->show($t,$s,$option);
                $html.= PHP_EOL.'';
                $html.= PHP_EOL.'';

       

                }else{

                    $msg_id = 0;
                    $html= '栏目不能为空';
                    $data= '栏目不能为空';
                }

                if (!$post['field']){

                    $msg_id = 0;
                    $html= '字段不能为空';
                    $data= '字段不能为空';
                }

        $this->_json($msg_id, $html, $data);

    }





    public function labelling() {

        $html = '';
        $post = $_POST;

        // 栏目循环
        if(!$post['module']){
            $html='模块不能为空';
            $data='模块不能为空';
            $msg_id = 0;
            $this->_json($msg_id, $html, $data);
        }

                $html.= '{category module='.$post['module'];

                    if ($post['site']) {
                        $html.= ' site='.intval($post['site']);
                    }
                    if (strlen($post['catid'])&&!$post['id']) {
                        $html.= ' pid='.intval($post['catid']);
                    }
                    if ($post['id']) {
                        $html.= ' id='.$post['id'];
                    }
                    if ($post['num']) {
                        $html.= ' num='.$post['num'];
                    }

                        $html.= ' order=displayorder';

                    if ($post['cache']) {
                        $html.= ' cache='.intval($post['cache']);
                    }

                    if ($post['return'] && $post['return']!=='t') {
                        $t = $post['return'];
                        $return = ' return='.$post['return'].'}';
                    }else{
                        $t = 't';
                        $return = '}';
                    }

                $t = $t . '.';

                $html.= $return;


                $html.= PHP_EOL.'<li><a href="{$'.$t.'url}" title="{$'.$t.'name}">{$'.$t.'name}</a></li>';
                // 自定义字段
                $html.= PHP_EOL.'{/category}';
                $html.= PHP_EOL.'';


                if($post['module'] == 'share'){
                    $cate_file_mod = dr_share_cat_value($post['catid'],'mid');
                }else{
                    $cate_file_mod = $post['module'];
                }
                //字段获取
                //$diyfiled = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$cate_file_mod,'category_data_field');


        $diyfiled = \Phpcmf\Service::M()->table('field')
            ->where('disabled', 0)
            ->where('relatedname', 'category-'.$post['module'])
            ->getAll();



                //var_dump($diyfiled);exit;

                if($diyfiled){
                    $filed = array_merge($diyfiled,$this->sys_field);
                }

                $data = '';


                    foreach ($filed as $s) {
                            $data.= PHP_EOL.$s['name'].'：';
                            
              
                            $data.= \Phpcmf\Service::M('Field', APP_DIR)->show($t,$s,$option);
                    }



                $msg_id = 1;

        $this->_json($msg_id, $html, $data);

    }

}
