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
                            'text' => __('Add permission limitation'),
                            'class' => 'btn btn-primary',
                            'popover_url' => '/PermissionLimitations/add'
                        ]
                    ]
                ],
                [
                    'type' => 'search',
                    'button' => __('Search'),
                    'placeholder' => __('Enter value to search'),
                    'data' => '',
                    'searchKey' => 'value'
                ]
            ]
        ],
        'fields' => [
            [
                'name' => '#',
                'sort' => 'id',
                'data_path' => 'id',
            ],
            [
                'name' => __('Scope'),
                'sort' => 'scope',
                'data_path' => 'scope',
            ],
            [
                'name' => __('Permission'),
                'sort' => 'permission',
                'data_path' => 'permission'
            ],
            [
                'name' => __('Limit'),
                'sort' => 'max_occurrence',
                'data_path' => 'max_occurrence'
            ],
            [
                'name' => __('Comment'),
                'sort' => 'comment',
                'data_path' => 'comment'
            ]
        ],
        'title' => __('Permission Limitations Index'),
        'description' => __('A list of configurable user roles. Create or modify user access roles based on the settings below.'),
        'pull' => 'right',
        'actions' => [
            [
                'url' => '/permissionLimitations/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye'
            ],
            [
                'open_modal' => '/permissionLimitations/edit/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'edit',
                'requirement' => !empty($loggedUser['role']['perm_admin'])
            ],
            [
                'open_modal' => '/permissionLimitations/delete/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'trash',
                'requirement' => !empty($loggedUser['role']['perm_admin'])
            ],
        ]
    ]
]);
echo '</div>';
?>
