<?php namespace Phpcmf\Controllers\Admin;

class Content extends \Phpcmf\App
{


    public function __construct(...$params)
    {
        parent::__construct(...$params);

        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '内容页调用' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-tag'],
                ]
            ),
        ]);
    }


    public function index() {

        \Phpcmf\Service::V()->display('content.html');
    }

    public function labelling() {

            $html = '';
            $data = '';
            $post = $_POST;
            $msg_id = 0;

        switch (intval($_GET['id'])) {

            case 1:

                $html.= '{content';

                if($post['module']){

                    $html.= ' module='.$post['module'];

                }else{

                    $html='未选择模块';
                    $data='未选择模块';
                    $msg_id = 0;
                    break;
                    
                }

                if(intval($post['contentid'])){

                    $html.= ' id='.intval($post['contentid']);

                }else{

                    $html='文章id不能为空，且必须为数字';
                    $data='文章id不能为空，且必须为数字';
                    $msg_id = 0;
                    break;
                    
                }


                if(!$post['field']){

                    $html='字段未选择';
                    $data='字段未选择';
                    $msg_id = 0;
                    break;
                    
                }


                if ($post['cache']) {
                    $html.= ' cache='.intval($post['cache']);
                }

                if ($post['return']&&$post['return']!='t') {
                    $t = $post['return'].'.';
                    $return = ' return='.$post['return'].'}';
                }else{
                    $t = 't.';
                    $return = '}';
                }

                $html.= $return;

                $html.= PHP_EOL.'   主题：{'.$t.'title}';
                // 自定义字段
                $html.= PHP_EOL.'{/content}'.PHP_EOL.PHP_EOL;

                $module_field = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-'.$post['module']);
                //$field_list = $module_field['field'];
                // 默认字段
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

                            
                            $data.= \Phpcmf\Service::M('Field', APP_DIR)->show($t,$field_arr,$option);
                            $data.= PHP_EOL.'';
                            $data.= PHP_EOL.'';
                        }
                        //var_dump($field_arr);

                }



                break;

        }

        $this->_json(1, $html, $data);

    }

    public function sitemodule(){

            $html = '';
            $data = '';
            $post = $_POST;
            $msg_id = 0;

            $site = $post['site'] ? $post['site'] : SITE_ID;

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

    public function show(){

            $html = '';
            $data = '';
            $post = $_POST;
            $msg_id = 0;


            switch ($post['show']) {

                case 'cat':
                        $html.= '{$'.$post['show'].'}';
                        $html.= PHP_EOL.'';
                        $html.= PHP_EOL.'数组如何得到想要的内容？写在show.html';
                        $html.= PHP_EOL.'<?php';
                        $html.= PHP_EOL.'echo "<pre>" . print_r($'.$post['show'].', 1) . "</pre>";';
                        $html.= PHP_EOL.'?>';
                        $html.= PHP_EOL.'';
                        $html.= PHP_EOL.'对照上面的层级关系写：';
                        $html.= PHP_EOL.'栏目id：{$'.$post['show'].'[\'id\']}';
                        $html.= PHP_EOL.'栏目关键字：{$'.$post['show'].'[\'setting\'][\'seo\'][\'list_keywords\']}';

                    break;

                case 'parent':
                        $html.= '{$'.$post['show'].'}';
                        $html.= PHP_EOL.'';
                        $html.= PHP_EOL.'数组如何得到想要的内容？写在show.html';
                        $html.= PHP_EOL.'<?php';
                        $html.= PHP_EOL.'echo "<pre>" . print_r($'.$post['show'].', 1) . "</pre>";';
                        $html.= PHP_EOL.'?>';
                        $html.= PHP_EOL.'';
                        $html.= PHP_EOL.'对照上面的层级关系写：';
                        $html.= PHP_EOL.'父栏目id：{$'.$post['show'].'[\'id\']}';
                        $html.= PHP_EOL.'父栏目关键字：{$'.$post['show'].'[\'setting\'][\'seo\'][\'list_keywords\']}';

                    break;

                case 'related':

                        $html.= '调用栏目下级或者同级栏目，当栏目存在下级栏目时就调用下级栏目，如果不存在下级栏目就调用当前栏目的同级栏目';
                        $html.= PHP_EOL.'适用于 search.html list.html category.html show.html page.html模板';
                        $html.= PHP_EOL.'';
                        $html.= PHP_EOL.'{loop $related $c}';
                        $html.= PHP_EOL.'<li {if $c.id==$cat.id} class="active"{/if}><a href="{$c.url}">{$c.name}</a></li>';
                        $html.= PHP_EOL.'{/loop}';
                        $html.= PHP_EOL.'';
                        $html.= PHP_EOL.'判断当前栏目是否含有子栏目';
                        $html.= PHP_EOL.'{if $cat.child}';
                        $html.= PHP_EOL.'有';
                        $html.= PHP_EOL.'{else}';
                        $html.= PHP_EOL.'没有';
                        $html.= PHP_EOL.'{/if}';

                    break;

                case 'prev_page':

                        $html.= '<strong>上一篇：</strong>';
                        $html.= PHP_EOL.'{if $prev_page}';
                        $html.= PHP_EOL.'    <a href="{$prev_page.url}">{$prev_page.title}</a>';
                        $html.= PHP_EOL.'{else}';
                        $html.= PHP_EOL.'    没有了';
                        $html.= PHP_EOL.'{/if}';

                    break;

                case 'next_page':

                        $html.= '<strong>下一篇：</strong>';
                        $html.= PHP_EOL.'{if $next_page}';
                        $html.= PHP_EOL.'    <a href="{$next_page.url}">{$next_page.title}</a>';
                        $html.= PHP_EOL.'{else}';
                        $html.= PHP_EOL.'    没有了';
                        $html.= PHP_EOL.'{/if}';

                    break;

                case 'tags':
                        $html.= '文章关键词';
                        $html.= PHP_EOL.'';
                        $html.= PHP_EOL.'注意：这个不是tag插件，是这篇文章的关键词';
                        $html.= PHP_EOL.'';
                        $html.= PHP_EOL.'{loop $tags $name $url}';
                        $html.= PHP_EOL.'    <a href="{$url}" target="_blank">{$name}</a>';
                        $html.= PHP_EOL.'{/loop}';
                    break;

                case 'hits':
                        $html.= '阅读次数，固定不会变：{$hits}';
                        $html.= PHP_EOL.'';
                        $html.= PHP_EOL.'';
                        $html.= PHP_EOL.'其实show.html页是用这个，浏览时会+1:';
                        $html.= PHP_EOL.'{dr_show_hits($id)}';
                    break;

                case 'content':
                        $html.= '文章内容：{$content}';
                        $html.= PHP_EOL.'内链调用方法：';
                        $html.= PHP_EOL.'{dr_content_link($tags, $content)} ';
                        $html.= PHP_EOL.'';
                        $html.= PHP_EOL.'如果只关联一次，可以这样调用';
                        $html.= PHP_EOL.'{dr_content_link($tags, $content, 1)}';
                    break;







                default:
                    $html.= '{$'.$post['show'].'}';
             
            }
  


            $msg_id = 1;


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

                foreach ($filed as $t) {
                        $data.= '<option value="'.$t['fieldname'].'">'.$t['name'].'（'.$t['fieldname'].'）</option>';  
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
}
