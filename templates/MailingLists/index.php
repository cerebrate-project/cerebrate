<?php

echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'data' => $data,
        'top_bar' => [
            'children' => [
                [
                    'type' => 'simple',
                    'children' => [
                        'data' => [
                            'type' => 'simple',
                            'text' => __('Add mailing list'),
                            'popover_url' => '/MailingLists/add'
                        ]
                    ]
                ],
                [
                    'type' => 'search',
                    'button' => __('Filter'),
                    'placeholder' => __('Enter value to search'),
                    'data' => '',
                    'searchKey' => 'value'
                ],
                [
                    'type' => 'table_action',
                    'table_setting_id' => 'mailinglist_index',
                ]
            ]
        ],
        'fields' => [
            [
                'name' => '#',
                'sort' => 'id',
                'class' => 'short',
                'data_path' => 'id',
            ],
            [
                'name' => __('Name'),
                'sort' => 'name',
                'data_path' => 'name',
            ],
            [
                'name' => __('Owner'),
                'data_path' => 'user_id',
                'element' => 'user'
            ],
            [
                'name' => __('UUID'),
                'sort' => 'uuid',
                'data_path' => 'uuid',
            ],
            [
                'name' => __('Members'),
                'data_path' => 'individuals',
                'element' => 'count_summary',
            ],
            [
                'name' => __('Intended recipients'),
                'data_path' => 'recipients',
            ],
            [
                'name' => __('Description'),
                'data_path' => 'description',
            ],
            [
                'name' => __('Active'),
                'data_path' => 'active',
                'sort' => 'active',
                'element' => 'boolean',
            ],
            [
                'name' => __('Deleted'),
                'data_path' => 'deleted',
                'sort' => 'deleted',
                'element' => 'boolean',
            ],

            // [
            //     'name' => __('Members'),
            //     'data_path' => 'alignments',
            //     'element' =>  'count_summary',
            //     'url' => '/sharingGroups/view/{{id}}',
            //     'url_data_path' => 'id'
            // ]
        ],
        'title' => __('Mailing Lists Index'),
        'description' => __('Mailing list are email distribution lists containing individuals.'),
        'actions' => [
            [
                'url' => '/mailingLists/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye'
            ],
            [
                'open_modal' => '/mailingLists/edit/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'edit'
            ],
            [
                'open_modal' => '/mailingLists/delete/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'trash'
            ],
        ]
    ]
]);
?>
