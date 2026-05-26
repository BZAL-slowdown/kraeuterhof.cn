<?php

/**
 * 菜单配置
 */

return [

    'admin' => [



        'app-labeling' => [
                            'name' => '标签',
                            'icon' => 'fa fa-tag',
            'left' => [
                'app-labeling-my' => [
                            'name' => '快捷操作',
                            'icon' => 'bi bi-patch-question-fill',
                            'link' => [
 

                                 [
                                    'name' => '项目信息调用',
                                    'icon' => 'bi bi-justify',
                                    'uri' => 'labelling/siteinfo/index',
                                ],
                                 [
                                    'name' => '快捷字段调用',
                                    'icon' => 'bi bi-justify',
                                    'uri' => 'labelling/allfield/index',
                                ],
                                 [
                                    'name' => '作品展示',
                                    'icon' => 'bi bi-justify',
                                    'uri' => 'labelling/allfield/theme',
                                ],
                                 [
                                    'name' => '手册直达',
                                    'icon' => 'bi bi-justify',
                                    'uri' => 'labelling/help/index',
                                ],




                                

                            ],
                ],
                [
                            'name' => '循环调用',
                            'icon' => 'fa fa-television',
                            'link' => [
                                 [
                                    'name' => '栏目菜单',
                                    'icon' => 'bi bi-justify',
                                    'uri' => 'labelling/category/loop',
                                ],
                                 [
                                    'name' => '内容列表',
                                    'icon' => 'bi bi-justify',
                                    'uri' => 'labelling/module/loop',
                                ],

                            ],
                ],[

                    
                            'name' => '单独调用',
                            'icon' => 'fa fa-file-text-o',
                            'link' => [
                                 [
                                    'name' => '栏目菜单',
                                    'icon' => 'bi bi-justify',
                                    'uri' => 'labelling/category/index',
                                ],
                                 [
                                    'name' => '文章调用',
                                    'icon' => 'bi bi-justify',
                                    'uri' => 'labelling/content/index',
                                ],

                            ],
                ],[

                    
                            'name' => '表单调用',
                            'icon' => 'fa fa-tags',
                            'link' => [
                                 [
                                    'name' => '模块表单',
                                    'icon' => 'bi bi-justify',
                                    'uri' => 'labelling/allfield/mform',
                                ],
                                 [
                                    'name' => '网站表单',
                                    'icon' => 'bi bi-justify',
                                    'uri' => 'labelling/allfield/forms',
                                ],

                            ],
                ],






























            ],
        ],

    ],
];

