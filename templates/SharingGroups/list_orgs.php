<?php
echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'data' => $sharing_group_orgs,
        'skip_pagination' => 1,
        'top_bar' => [
            'children' => [
                [
                    'type' => 'simple',
                    'children' => [
                        'data' => [
                            'type' => 'simple',
                            'text' => __('Add member'),
                            'popover_url' => '/sharingGroups/addOrg/' . h($sharing_group_id),
                            'reload_url' => '/sharingGroups/listOrgs/' . h($sharing_group_id)
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
                'class' => 'short',
                'data_path' => 'id',
            ],
            [
                'name' => __('Name'),
                'class' => 'short',
                'data_path' => 'name',
            ],
            [
                'name' => __('UUID'),
                'sort' => 'uuid',
                'class' => 'short',
                'data_path' => 'uuid',
            ]
        ],
        'pull' => 'right',
        'actions' => [
            [
                'url' => '/organisations/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye'
            ],
            [
                'open_modal' => '/sharingGroups/removeOrg/' . h($sharing_group_id) . '/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'reload_url' => '/sharingGroups/listOrgs/' . h($sharing_group_id),
                'icon' => 'trash'
            ],
        ]
    ]
]);
echo '</div>';
?>
