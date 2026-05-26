<?php namespace Phpcmf\Controllers\Admin;

class Allfield extends \Phpcmf\App
{


    public function __construct(...$params)
    {
        parent::__construct(...$params);

        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '模块字段快速调用' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-tag'],
                ]
            ),
        ]);
    }


    public function index() {
        \Phpcmf\Service::V()->display('allfield.html');
    }

    public function mform() {
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '模块表单字段调用' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/mform', 'fa fa-tag'],
                ]
            ),
        ]);
        \Phpcmf\Service::V()->display('mform.html');
    }

    public function forms() {
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '网站表单字段调用' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/forms', 'fa fa-tag'],
                ]
            ),
        ]);
        \Phpcmf\Service::V()->display('forms.html');
    }

    public function theme() {
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '作品展示' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/theme', 'fa fa-tag'],
                ]
            ),
        ]);
        \Phpcmf\Service::V()->display('theme.html');
    }

    public function labelling() {

            $html = '';
            $data = '';
            $post = $_POST;
            $msg_id = 0;
            $option = '';

        switch (intval($_GET['id'])) {

            case 1:

                if ($post['page']==1) {
                    if ($post['return']) {
                        $t = $post['return'].'.';
                    }else{
                        $t = 't.';
                    }
                }else{
                    $t = '';
                }


                if (!$post['field']) {

                    $html.= '请先选择字段';
                    $data.= '请先选择字段';
                    $msg_id = 0;
                    break;

                }



                $module_field = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$post['module']);
                //$field_list = $module_field['field'];
                // 默认字段
                $module_field['field']['catid'] = [
                    'name' => dr_lang('栏目'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Catid',
                    'fieldname' => 'catid',
                ];
                $module_field['field']['inputtime'] = [
                    'name' => dr_lang('录入时间'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Date',
                    'fieldname' => 'inputtime',
                ];
                $module_field['field']['updatetime'] = [
                    'name' => dr_lang('更新时间'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Date',
                    'fieldname' => 'updatetime',
                ];




                foreach ($post['field'] as $field) {


                        $field_arr = $module_field['field'][$field]; //所有字段
                        if($field_arr['fieldtype']!='Merge' && $field_arr['fieldtype']!='Group'){

                            //$html.= PHP_EOL.$field_arr['name'].$field_arr['fieldtype'].$field_arr['id'].'：';
                            $html.= $field_arr['name'].'：';

                            if($field_arr['fieldtype']=='Linkage' || $field_arr['fieldtype']=='Linkages'){
                                $option = $field_arr['setting']['option']['linkage'];
                            }elseif($field_arr['fieldtype']=='Related'){
                                $option = $field_arr['setting']['option']['module'];

                            }

                            
                            $html.= \Phpcmf\Service::M('Field', APP_DIR)->show($t,$field_arr,$option);
                            $html.= PHP_EOL.'';
                            $html.= PHP_EOL.'';
                        }
                        //var_dump($field_arr);

                }

                $msg_id = 1;
                break;

        }

        $this->_json($msg_id, $html, $data);

    }

    public function sitemodule(){

            $html = '';
            $data = '';
            $post = $_POST;
            $msg_id = 0;

            $site = SITE_ID;

            $module = \Phpcmf\Service::L('cache')->get('module-'.$site.'-content');
            if($module){

                $data.= '<option>选择模块</option>';
                foreach ($module as $key => $t) {
                        $data.= '<option value="'.$key.'">'.$t['name'].'</option>';
                }
                $msg_id = 1;

            }else{

                $html='站点未安装模块';
                $data='站点未安装模块';
                $msg_id = 0;

            }
        $this->_json($msg_id, $html, $data);

    }

    public function field() {

            $html = '';
            $data = '';
            $post = $_POST;
            $msg_id = 0;

            
            $site = $post['site'] ? $post['site'] : SITE_ID;

            if($post['module']){
            
                //字段获取
                $filed = \Phpcmf\Service::L('cache')->get('module-'.$site.'-'.$post['module'],'field');

                // 默认字段
                $filed['catid'] = [
                    'name' => dr_lang('栏目'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Catid',
                    'fieldname' => 'catid',
                ];
                $filed['inputtime'] = [
                    'name' => dr_lang('录入时间'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Date',
                    'fieldname' => 'inputtime',
                ];
                $filed['updatetime'] = [
                    'name' => dr_lang('更新时间'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Date',
                    'fieldname' => 'updatetime',
                ];


                foreach ($filed as $t) {
                    if($t['fieldtype']!='Merge' && $t['fieldtype']!='Group'){
                        $data.= '<option value="'.$t['fieldname'].'">'.$t['name'].'（'.$t['fieldname'].'）</option>';  
                    }
                }
                //字段获取结束
                $msg_id = 1;
            }else{
                $html='模块不能为空';
                $data='模块不能为空';
                $msg_id = 0;
            }

        $this->_json($msg_id, $html, $data);
    }




    public function mformlist() {

            $html = '';
            $data = '<option value="0">请选择表单</option>';
            $post = $_POST;
            $msg_id = 0;

            
            $site = $post['site'] ? $post['site'] : SITE_ID;
            if($post['module']){
                $form = \Phpcmf\Service::L('cache')->get('module-'.$site.'-'.$post['module'], 'form');

                foreach ($form as $t) {

                        $data.= '<option value="'.$t['table'].'">'.$t['name'].'（'.$t['table'].'）</option>';  

                }
                //字段获取结束
                $msg_id = 1;
            }else{
                $html='模块表单不能为空';
                $data.= '<option value="">请先选择模块</option>';
                $msg_id = 0;
            }

        $this->_json($msg_id, $html, $data);

    }


    public function mformfield() {

            $html = '';
            $data = '';
            $post = $_POST;
            $msg_id = 0;

            
            $site = $post['site'] ? $post['site'] : SITE_ID;
            if($post['module']){
                $form = \Phpcmf\Service::L('cache')->get('module-'.$site.'-'.$post['module'], 'form');

                foreach ($form[$post['mform']]['field'] as $t) {

                        $data.= '<option value="'.$t['fieldname'].'">'.$t['name'].'（'.$t['fieldname'].'）</option>';  

                }
                //字段获取结束
                $msg_id = 1;
            }else{
                $html='模块表单不能为空';
                $data='模块表单不能为空';
                $msg_id = 0;
            }

        $this->_json($msg_id, $html, $data);

    }


    public function mformshow() {

            $html = '';
            $data = '';
            $post = $_POST;
            $msg_id = 0;
            $option = '';
            $site = $post['site'] ? $post['site'] : SITE_ID;

        switch (intval($_GET['id'])) {

            case 1:

                if ($post['page']==2) {
                    $t = '';
                    $return = '';
                }else{
                    if ($post['return']&&$post['return']!='t') {
                        $t = $post['return'].'.';
                        $return = ' return='.$post['return'];
                    }else{
                        $t = 't.';
                        $return = '';
                    }
                }


                if (!$post['field']) {

                    $html.= '请先选择字段';
                    $data.= '请先选择字段';
                    $msg_id = 0;
                    break;

                }

                $form = \Phpcmf\Service::L('cache')->get('module-'.$site.'-'.$post['module'], 'form');

                $module_field['field'] = $form[$post['mform']]['field'];

                $module_field['field']['inputtime'] = [
                    'name' => dr_lang('录入时间'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Date',
                    'fieldname' => 'inputtime',
                ];
if ($post['page']==1) {
    $html.= "{mform form=".$post['mform']." module=".$post['module']." order=displayorder,inputtime num=10".$return."}".PHP_EOL;
}
if ($post['page']==3) {
    $html.= "{mform form=".$post['mform']." module=".$post['module']." order=displayorder,inputtime page=1 pagesize=5 urlrule=index.php?page=[page]".$return."}".PHP_EOL;
}

if ($post['page']!=2) {
    $html.= "   主题：{"."$".$t."title}".PHP_EOL;
}else{
    $html.= "参考下框 “更多字段” 数据".PHP_EOL;
}

if ($post['page']==1) {
    $html.= "{/mform}".PHP_EOL.PHP_EOL;
}
if ($post['page']==3) {
    $html.= "{/mform}".PHP_EOL;
    $html.= "<!--分页输出start-->".PHP_EOL;
    $html.= "{"."$"."pages"."}".PHP_EOL;
    $html.= "<!--分页输出over-->".PHP_EOL;
    $html.= "{"."$"."error"."}".PHP_EOL.PHP_EOL;
}


$data = '';
                foreach ($post['field'] as $field) {


                        $field_arr = $module_field['field'][$field]; //所有字段
                        if($field_arr['fieldtype']!='Merge' && $field_arr['fieldtype']!='Group'){

                            //$html.= PHP_EOL.$field_arr['name'].$field_arr['fieldtype'].$field_arr['id'].'：';
                            $data.= $field_arr['name'].'：';

                            if($field_arr['fieldtype']=='Linkage' || $field_arr['fieldtype']=='Linkages'){
                                $option = $field_arr['setting']['option']['linkage'];
                            }elseif($field_arr['fieldtype']=='Related'){
                                $option = $field_arr['setting']['option']['module'];

                            }

                            if($t) $option['pix'] = $t.'.';

                            $data.= \Phpcmf\Service::M('Field', APP_DIR)->show($t,$field_arr,$option);
                            $data.= PHP_EOL.'';
                            $data.= PHP_EOL.'';
                        }
                        //var_dump($field_arr);

                }


                $msg_id = 1;
                break;

        }

        $this->_json($msg_id, $html, $data);

    }










    public function formslist() {

            $html = '';
            $data = '<option value="0">请选择表单</option>';
            $post = $_POST;
            $msg_id = 0;

            
            $site = $post['site'] ? $post['site'] : SITE_ID;

                $form = \Phpcmf\Service::L('cache')->get('form-'.SITE_ID);

                foreach ($form as $t) {

                        $data.= '<option value="'.$t['table'].'">'.$t['name'].'（'.$t['table'].'）</option>';  

                }
                //字段获取结束
                $msg_id = 1;


        $this->_json($msg_id, $html, $data);

    }


    public function formsfield() {

            $html = '';
            $data = '';
            $post = $_POST;
            $msg_id = 0;

            
            $site = $post['site'] ? $post['site'] : SITE_ID;
            if($post['forms']){
                $form = \Phpcmf\Service::L('cache')->get('form-'.SITE_ID);

                foreach ($form[$post['forms']]['field'] as $t) {

                        $data.= '<option value="'.$t['fieldname'].'">'.$t['name'].'（'.$t['fieldname'].'）</option>';  

                }
                //字段获取结束
                $msg_id = 1;
            }else{
                $html='网站表单不能为空';
                $data='网站表单不能为空';
                $msg_id = 0;
            }

        $this->_json($msg_id, $html, $data);

    }

    public function formsshow() {

            $html = '';
            $data = '';
            $post = $_POST;
            $msg_id = 0;
            $option = [];
            $site = $post['site'] ? $post['site'] : SITE_ID;

        switch (intval($_GET['id'])) {

            case 1:

                if ($post['page']==2) {
                    $t = '';
                    $return = '';
                }else{
                    if ($post['return']&&$post['return']!='t') {
                        $t = $post['return'].'.';
                        $return = ' return='.$post['return'];
                    }else{
                        $t = 't.';
                        $return = '';
                    }
                }


                if (!$post['field']) {

                    $html.= '请先选择字段';
                    $data.= '请先选择字段';
                    $msg_id = 0;
                    break;

                }

                $form = \Phpcmf\Service::L('cache')->get('form-'.SITE_ID);

                $module_field['field'] = $form[$post['forms']]['field'];

                $module_field['field']['inputtime'] = [
                    'name' => dr_lang('录入时间'),
                    'ismain' => 1,
                    'ismember' => 1,
                    'fieldtype' => 'Date',
                    'fieldname' => 'inputtime',
                ];
if ($post['page']==1) {
    $html.= "{form form=".$post['forms']." order=displayorder,inputtime num=10".$return."}".PHP_EOL;
}
if ($post['page']==3) {
    $html.= "{form form=".$post['forms']." order=displayorder,inputtime page=1 pagesize=5 urlrule=index.php?page=[page]".$return."}".PHP_EOL;
}
if ($post['page']!=2) {
    $html.= "   主题：{"."$".$t."title}".PHP_EOL;
}else{
    $html.= "参考下框 “更多字段” 数据".PHP_EOL;
}


if ($post['page']==1) {
    $html.= "{/form}".PHP_EOL.PHP_EOL;
}
if ($post['page']==3) {
    $html.= "{/form}".PHP_EOL;
    $html.= "<!--分页输出start-->".PHP_EOL;
    $html.= "{"."$"."pages"."}".PHP_EOL;
    $html.= "<!--分页输出over-->".PHP_EOL;
    $html.= "{"."$"."error"."}".PHP_EOL.PHP_EOL;
}


$data = '';

                foreach ($post['field'] as $field) {


                        $field_arr = $module_field['field'][$field]; //所有字段

                        if($field_arr['fieldtype']=='Merge' || $field_arr['fieldtype']=='Group'){
                            continue;
                        }


                        //$html.= PHP_EOL.$field_arr['name'].$field_arr['fieldtype'].$field_arr['id'].'：';
                        $data.= $field_arr['name'].'：';

                        if($field_arr['fieldtype']=='Linkage' || $field_arr['fieldtype']=='Linkages'){
                            $option = $field_arr['setting']['option']['linkage'];
                        }elseif($field_arr['fieldtype']=='Related'){
                            $option = $field_arr['setting']['option']['module'];

                        }

//var_dump($option);
                        if($t) {
                            $option['pix'] = $t.'.';
                        }

                        $data.= \Phpcmf\Service::M('Field', APP_DIR)->show($t,$field_arr,$option);
                        $data.= PHP_EOL.'';
                        $data.= PHP_EOL.'';
                        
                        

                }

                $msg_id = 1;
                break;

        }
        $this->_json($msg_id, $html, $data);

    }






}
