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
                            'text' => __('Add sharing group'),
                            'class' => 'btn btn-primary',
                            'popover_url' => '/SharingGroups/add'
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
            ],
            [
                'name' => __('Members'),
                'data_path' => 'alignments',
                'element' =>  'count_summary',
                'url' => '/sharingGroups/view/{{id}}',
                'url_data_path' => 'id'
            ]
        ],
        'title' => __('Sharing Groups Index'),
        'description' => __('Sharing groups are distribution lists usable by tools that can exchange information with a list of trusted partners. Create recurring or ad hoc sharing groups and share them with the members of the sharing group.'),
        'pull' => 'right',
        'actions' => [
            [
                'url' => '/sharingGroups/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye'
            ],
            [
                'onclick' => 'populateAndLoadModal(\'/sharingGroups/edit/[onclick_params_data_path]\');',
                'onclick_params_data_path' => 'id',
                'icon' => 'edit'
            ],
            [
                'onclick' => 'populateAndLoadModal(\'/sharingGroups/delete/[onclick_params_data_path]\');',
                'onclick_params_data_path' => 'id',
                'icon' => 'trash'
            ]
        ]
    ]
]);
echo '</div>';
?>
