<?php
$topbarChildren = [];
if (!empty($loggedUser->role->perm_community_admin)) {
    $topbarChildren[] =  [
        'type' => 'simple',
        'children' => [
            'data' => [
                'type' => 'simple',
                'text' => __('Add Enumeration Collection'),
                'class' => 'btn btn-primary',
                'popover_url' => '/enumerationCollections/add'
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
                'name' => __('Enabled'),
                'sort' => 'enabled',
                'data_path' => 'enabled',
            ],
            [
                'name' => __('UUID'),
                'sort' => 'uuid',
                'data_path' => 'uuid',
            ],
            [
                'name' => __('Model'),
                'sort' => 'target_model',
                'data_path' => 'target_model',
            ],
            [
                'name' => __('Field'),
                'sort' => 'target_field',
                'data_path' => 'target_field',
            ],
            [
                'name' => __('Values'),
                'sort' => 'value_count',
                'data_path' => 'value_count',
            ],
            [
                'name' => __('Description'),
                'data_path' => 'description',
            ],
        ],
        'title' => __('Enumeration Collections Index'),
        'description' => __('A list collections that can be used to convert string input fields into selections wherever it makes sense.'),
        'pull' => 'right',
        'actions' => [
            [
                'url' => '/enumerationCollections/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye'
            ],
            [
                'open_modal' => '/enumerationCollections/edit/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'edit',
                'requirement' => !empty($loggedUser['role']['perm_community_admin'])
            ],
            [
                'open_modal' => '/enumerationCollections/delete/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'trash',
                'requirement' => !empty($loggedUser['role']['perm_community_admin'])
            ],
        ]
    ]
]);
echo '</div>';
?>
