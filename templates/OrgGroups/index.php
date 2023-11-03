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
                            'text' => __('Add group'),
                            'class' => 'btn btn-primary',
                            'popover_url' => '/orgGroups/add',
                            'requirement' => !empty($loggedUser['role']['perm_admin']),
                        ]
                    ]
                ],
                [
                    'type' => 'search',
                    'button' => __('Search'),
                    'placeholder' => __('Enter value to search'),
                    'data' => '',
                    'searchKey' => 'value',
                    'allowFilering' => true
                ],
                [
                    'type' => 'table_action',
                    'table_setting_id' => 'org_groups_index',
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
                'class' => 'short',
                'data_path' => 'name',
                'sort' => 'name',
            ],
            [
                'name' => __('UUID'),
                'sort' => 'uuid',
                'class' => 'short',
                'data_path' => 'uuid',
            ],
            [
                'name' => __('Description'),
                'data_path' => 'description',
                'sort' => 'description',
            ],
            [
                'name' => __('Tags'),
                'data_path' => 'tags',
                'element' => 'tags',
            ],
        ],
        'title' => __('Organisation Groups Index'),
        'description' => __('OrgGroups are an administrative concept, multiple organisations can belong to a grouping that allows common management by so called "GroupAdmins". This helps grouping organisations by sector, country or other commonalities into co-managed sub-communities.'),
        'includeAllPagination' => true,
        'actions' => [
            [
                'url' => '/orgGroups/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye'
            ],
            [
                'open_modal' => '/orgGroups/edit/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'edit',
                'requirement' => $loggedUser['role']['perm_admin']
            ],
            [
                'open_modal' => '/orgGroups/delete/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'trash',
                'requirement' => $loggedUser['role']['perm_admin']
            ],
        ]
    ]
]);
echo '</div>';
?>
