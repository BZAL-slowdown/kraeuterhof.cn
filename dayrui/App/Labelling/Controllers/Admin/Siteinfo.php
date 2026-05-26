<?php namespace Phpcmf\Controllers\Admin;

class Siteinfo extends \Phpcmf\App
{


    public function __construct(...$params)
    {
        parent::__construct(...$params);



            // 默认字段
            $this->field['SITE_NAME'] = [
                'name' => dr_lang('项目名称'),
                'ismain' => 1,
                'ismember' => 1,
                'fieldtype' => 'Text',
                'fieldname' => 'SITE_NAME',
            ];
            $this->field['logo'] = [
                'name' => dr_lang('Logo'),
                'ismain' => 1,
                'ismember' => 1,
                'fieldtype' => 'File',
                'fieldname' => 'logo',
            ];
            $this->field['SITE_ICP'] = [
                'name' => dr_lang('ICP备案信息'),
                'ismain' => 1,
                'ismember' => 1,
                'fieldtype' => 'Text',
                'fieldname' => 'SITE_ICP',
            ];
            
            $site = $post['site'] ? $post['site'] : SITE_ID;


        $field0 = \Phpcmf\Service::M()->db->table('field')
            ->where('disabled', 0)
            ->where('ismain', 1)
            ->where('relatedname', 'site')
            ->where('relatedid', $site)
            ->orderBy('displayorder ASC,id ASC')
            ->get()->getResultArray();


        foreach ($field0 as $key => $value) {
            $this->field[$value['fieldname']] = $value;
        }



        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '模块字段快速调用' => [APP_DIR.'/'.\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-tag'],
                ]
            ),
            'field' => $this->field,
        ]);
    }


    public function index() {
        \Phpcmf\Service::V()->display('siteinfo.html');
    }


    public function labelling() {

            $html = '';
            $data = '';
            $post = $_POST;
            $msg_id = 0;
            $option = '';

 

            if (!$post['field']) {
                $html.= '请先选择字段';
                $data.= '请先选择字段';
                $msg_id = 0;
                $this->_json($msg_id, $html, $data);
            }


            foreach ($post['field'] as $field) {

                    $field_arr = $this->field[$field]; //所有字段


                    if($field_arr['fieldtype']!='Merge' && $field_arr['fieldtype']!='Group'){

                        //$html.= PHP_EOL.$field_arr['name'].$field_arr['fieldtype'].$field_arr['id'].'：';
                        $html.= $field_arr['name'].' / '.$field_arr['fieldtype'].'：';

                        if($field_arr['fieldtype']=='Linkage' || $field_arr['fieldtype']=='Linkages'){
                            $option = $field_arr['setting']['option']['linkage'];
                        }elseif($field_arr['fieldtype']=='Related'){
                            $option = $field_arr['setting']['option']['module'];

                        }

                        $html.= PHP_EOL.'';
                        if($field_arr['fieldtype']=='Text'){
                        $html.= PHP_EOL.'   直接调用：';
                        $html.= PHP_EOL.'      {dr_site_value(\''.$field.'\')}';
                        $html.= PHP_EOL.'';
                        }


                        $html.= PHP_EOL.'   其他调用方法：';
                        $html.= PHP_EOL.'   初始化：<!--初始化 $'.$field.' 之后，下面才可以调用-->';
                        $html.= PHP_EOL.'   {php $'.$field.' = dr_site_value(\''.$field.'\');}';
                        $html.= PHP_EOL.'';
                        $html.= \Phpcmf\Service::M('Field', APP_DIR)->show($t, $field_arr, $option);
                        $html.= PHP_EOL.'';
                        $html.= PHP_EOL.'';
                    }

            }

            $msg_id = 1;

   

        $this->_json($msg_id, $html, $data);

    }



    public function field() {

            $html = '';
            $data = '';
            $post = $_POST;
            $msg_id = 0;
            $field = [];



            foreach ($this->field as $t) {
                if($t['fieldtype']!='Merge' && $t['fieldtype']!='Group'){
                    $data.= '<option value="'.$t['fieldname'].'">'.$t['name'].'（'.$t['fieldname'].'）</option>';  
                }
            }
            //字段获取结束
            $msg_id = 1;
     

        $this->_json($msg_id, $html, $data);
    }


















}
