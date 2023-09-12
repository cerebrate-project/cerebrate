<?php
echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'skip_pagination' => true,
        'data' => $data,
        'top_bar' => [
            'children' => [
                [
                    'type' => 'simple',
                    'children' => [
                        'data' => [
                            'type' => 'simple',
                            'text' => __('Add Organisation'),
                            'class' => 'btn btn-primary',
                            'popover_url' => '/orgGroups/attachOrg/' . h($groupId),
                            'reload_url' => '/orgGroups/listOrgs/' . h($groupId),
                            'requirement' => $canEdit
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
            ]
        ],
        'title' => null,
        'description' => null,
        'actions' => [
            [
                'url' => '/organisations/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye'
            ],
            [
                'open_modal' => '/orgGroups/detachOrg/' . h($groupId) . '/[onclick_params_data_path]',
                'reload_url' => '/orgGroups/listOrgs/' . h($groupId),
                'modal_params_data_path' => 'id',
                'icon' => 'unlink',
                'title' => __('Remove organisation from the group'),
                'requirement' => $canEdit
            ],
        ]
    ]
]);
echo '</div>';
?>
