<?php
echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'data' => $data,
        'top_bar' => [
            'pull' => 'right',
            'children' => [
                [
                    'type' => 'simple',
                    'children' => [
                        'data' => [
                            'type' => 'simple',
                            'text' => __('Add role'),
                            'class' => 'btn btn-primary',
                            'popover_url' => '/roles/add'
                        ]
                    ]
                ],
                [
                    'type' => 'search',
                    'button' => __('Filter'),
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
                'name' => __('Name'),
                'sort' => 'name',
                'data_path' => 'name',
            ],
            [
                'name' => __('UUID'),
                'sort' => 'uuid',
                'data_path' => 'uuid',
                'placeholder' => __('Leave empty to auto generate')
            ],
            [
                'name' => __('Admin'),
                'sort' => 'perm_admin',
                'data_path' => 'perm_admin',
                'element' => 'boolean'
            ],
            [
                'name' => 'Default',
                'sort' => 'is_default',
                'data_path' => 'is_default',
                'element' => 'boolean'
            ],
        ],
        'title' => __('Roles Index'),
        'description' => __('A list of configurable user roles. Create or modify user access roles based on the settings below.'),
        'pull' => 'right',
        'actions' => [
            [
                'url' => '/roles/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye'
            ],
            [
                'onclick' => 'populateAndLoadModal(\'/roles/edit/[onclick_params_data_path]\');',
                'onclick_params_data_path' => 'id',
                'icon' => 'edit',
            ],
            [
                'onclick' => 'populateAndLoadModal(\'/roles/delete/[onclick_params_data_path]\');',
                'onclick_params_data_path' => 'id',
                'icon' => 'trash'
            ]
        ]
    ]
]);
echo '</div>';
?>
