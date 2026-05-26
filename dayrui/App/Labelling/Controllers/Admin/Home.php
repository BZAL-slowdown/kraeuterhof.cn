<?php namespace Phpcmf\Controllers\Admin;

class Home extends \Phpcmf\App
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
        'url' => [
            'name' => '地址',
            'ismain' => 1,
            'fieldtype' => 'Text',
            'fieldname' => 'url',
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
        'total' => [
            'name' => '内容总计',
            'ismain' => 1,
            'fieldtype' => 'Text',
            'fieldname' => 'total',
        ],
    ];

    public function __construct(...$params)
    {
        parent::__construct(...$params);

        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '栏目菜单调用' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-tag'],
                ]
            ),
        ]);
    }


    public function index() {

        $content = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');

        $module = [
            'share' => [
                'name' => '共享',
                'dirname' => 'share',
            ],
        ];
        if ($content) {
            foreach ($content as $i => $t) {
                if (!$t['share']) {
                    $module[$i] = $t;
                }
            }
        }
        $field = $this->sys_field;
        \Phpcmf\Service::V()->assign([
            'field' => $field,
            'module' => $module,
        ]);

        \Phpcmf\Service::V()->display('index.html');
    }

    public function labelling() {

        $html = '';
        $post = $_POST;

        if (!$post['moshi']){
            $msg_id = 0;
            $html= '选择输出模式';
            $data= '选择输出模式';
            $this->_json($msg_id, $html, $data);
            exit;
        }


        switch (intval($post['moshi'])) {

            case 2:
                // 栏目循环
                if(!$post['module']){
                    $html='模块不能为空';
                    $data='模块不能为空';
                    $msg_id = 0;
                    break;
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
                $html.= $return;


                $html.= PHP_EOL.'<li><a href="{$'.$t.'.url}" title="{$'.$t.'.name}">{$'.$t.'.name}</a></li>';
                // 自定义字段
                $html.= PHP_EOL.'{/category}';
                $html.= PHP_EOL.'';

                //字段获取
                //$diyfiled = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$post['module'],'category_field');
                $diyfiled = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$post['module'],'category_data_field');


                    if($diyfiled){
                        $filed = array_merge($diyfiled,$this->sys_field);
                    }else{
                        $filed = $this->sys_field;
                    }

                    foreach ($this->sys_field as $s) {
                            $html.= PHP_EOL.$s['name'].'：';
                            $html.= \Phpcmf\Service::M('Field', APP_DIR)->show($t,$s,$option);
                    }


                    foreach ($diyfiled as $f) {
                            $html.= PHP_EOL.$f['name'].'：';
                            //$data.= '<option value="'.$t['fieldname'].'">'.$t['name'].'（'.$t['fieldname'].'）</option>';  

                            //$html.= PHP_EOL.$t['name']
                            $html.= \Phpcmf\Service::M('Field', APP_DIR)->show($t,$f,$option);
                    }


                $msg_id = 1;

                break;

            case 1:


                //栏目独立调用
                if ($post['module']=='share') {
                    $share = 'share_';
                }else{
                    $module_name = '"'.$post['module'].'",';
                }


                if ($post['catid']){
                    $msg_id = 1;
                    $html= '指定栏目输出代码：';
                    $html.= PHP_EOL.'{dr_'.$share.'cat_value('.$module_name.$post['catid'].',\''.$post['field'].'\')}';
                    $html.= PHP_EOL.'';
                    $html.= PHP_EOL.'过滤Html';
                    $html.= PHP_EOL.'   {dr_clearhtml(dr_'.$share.'cat_value('.$module_name.$post['catid'].',\''.$post['field'].'\'))}';

                    $html.= PHP_EOL.'截取';
                    $html.= PHP_EOL.'   {dr_strcut(dr_clearhtml(dr_'.$share.'cat_value('.$module_name.$post['catid'].',\''.$post['field'].'\')), 10, "...")}';
                    $html.= PHP_EOL.'';
                    $html.= PHP_EOL.'Tips：如果是编辑器的内容，截取前需要先过滤Html';

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

                break;

        }

        $this->_json($msg_id, $html, $data);

    }

    public function catstree() {

            //栏目获取
            $html = '<option value="">选择栏目</option>';
            $post = $_POST;

            //$cats = \Phpcmf\Service::C()->get_cache('module-'.SITE_ID.'-'.$post['module'], 'category');
            $cats = \Phpcmf\Service::L('category', 'module')->get_category($post['module'], SITE_ID);

            if($post['moshi']==2){
                $html = $html.'<option value="0">顶级栏目循环</option>';
            }

            $catstree=$this->generateTree($cats);

            foreach ($catstree as $t) {
                    $html.= '<option value="'.$t['id'].'">'.$t['name'].'</option>';
                    foreach ($t['son'] as $tt) {
                        $html.= '<option value="'.$tt['id'].'">&nbsp;&nbsp;&nbsp;├&nbsp;'.$tt['name'].'</option>';
                        foreach ($tt['son'] as $ttt) {
                            $html.= '<option value="'.$ttt['id'].'">&nbsp;&nbsp;&nbsp;├&nbsp;├&nbsp;'.$ttt['name'].'</option>';
                            foreach ($ttt['son'] as $tttt) {
                                $html.= '<option value="'.$tttt['id'].'">&nbsp;&nbsp;&nbsp;├&nbsp;├&nbsp;├&nbsp;'.$tttt['name'].'</option>';
                            }
                        }
                    }   
            }
            //栏目获取结束


        $this->_json(1, $html,$data);
    }







    public function cate_field() {

            $post = $_POST;
            //字段获取
            //$diyfiled = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$post['module'],'category_field');

            if($post['module'] == 'share'){
                $cate_file_mod = dr_share_cat_value($post['catid'],'mid');
            }else{
                $cate_file_mod = $post['module'];
            }

            $diyfiled = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$cate_file_mod,'category_data_field');



            if($diyfiled){
                $filed = $diyfiled;
            }

  //var_dump($filed);exit;
                

            $data='<option value="">默认</option>';

            if($post['module']){
                $data.= '<optgroup label="系统字段">';
                foreach ($this->sys_field as $t) {
                        $data.= '<option value="'.$t['fieldname'].'">'.$t['name'].'（'.$t['fieldname'].'）</option>';  
                }
                $data.= '</optgroup>';

                if($filed){
                    $data.= '<optgroup label="自定义字段">';
                    foreach ($filed as $t) {
                            $data.= '<option value="'.$t['fieldname'].'">'.$t['name'].'（'.$t['fieldname'].'）</option>';  
                    }
                    $data.= '</optgroup>';
                }
            }
            //字段获取结束

            $this->_json(1, $html,$data);
}







    //递归
    public function generateTree($items){
        $tree = array();
        foreach($items as $item){
            if(isset($items[$item['pid']])){
                $items[$item['pid']]['son'][] =& $items[$item['id']];
            }else{
                $tree[] =& $items[$item['id']];
            }
        }
        return $tree;
    }


}
