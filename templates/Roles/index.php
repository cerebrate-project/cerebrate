<?php
$topbarChildren = [];
if (!empty($loggedUser->role->perm_admin)) {
    $topbarChildren[] =  [
        'type' => 'simple',
        'children' => [
            'data' => [
                'type' => 'simple',
                'text' => __('Add role'),
                'class' => 'btn btn-primary',
                'popover_url' => '/roles/add'
            ]
        ]
    ];
}
$topbarChildren[] = [
    'type' => 'search',
    'button' => __('Search'),
    'placeholder' => __('Enter value to search'),
    'data' => '',
    'searchKey' => 'value'
];

echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'data' => $data,
        'top_bar' => [
            'children' => $topbarChildren,
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
                'name' => __('Org Admin'),
                'sort' => 'perm_org_admin',
                'data_path' => 'perm_org_admin',
                'element' => 'boolean'
            ],
            [
                'name' => __('Sync'),
                'sort' => 'perm_sync',
                'data_path' => 'perm_sync',
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
                'open_modal' => '/roles/edit/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'edit',
                'requirement' => !empty($loggedUser['role']['perm_admin'])
            ],
            [
                'open_modal' => '/roles/delete/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'trash',
                'requirement' => !empty($loggedUser['role']['perm_admin'])
            ],
        ]
    ]
]);
echo '</div>';
?>
