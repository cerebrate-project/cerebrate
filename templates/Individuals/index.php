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
                            'text' => __('Add individual'),
                            'popover_url' => '/individuals/add'
                        ]
                    ]
                ],
                [
                    'type' => 'context_filters',
                    'context_filters' => $filteringContexts
                ],
                [
                    'type' => 'search',
                    'button' => __('Filter'),
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
                'data_path' => 'id',
            ],
            [
                'name' => __('Email'),
                'sort' => 'email',
                'data_path' => 'email',
            ],
            [
                'name' => __('First Name'),
                'sort' => 'first_name',
                'data_path' => 'first_name',
            ],
            [
                'name' => __('Last Name'),
                'sort' => 'last_name',
                'data_path' => 'last_name',
            ],
            [
                'name' => __('Alignments'),
                'data_path' => 'alignments',
                'element' => 'alignments',
                'scope' => $alignmentScope
            ],
            [
                'name' => __('Tags'),
                'data_path' => 'tags',
                'element' => 'tags',
            ],
            [
                'name' => __('UUID'),
                'sort' => 'uuid',
                'data_path' => 'uuid',
                'placeholder' => __('Leave empty to auto generate')
            ],
        ],
        'title' => __('ContactDB Individuals Index'),
        'description' => __('A list of individuals known by your Cerebrate instance. This list can get populated either directly, by adding new individuals or by fetching them from trusted remote sources. Additionally, users created for the platform will always have an individual identity.'),
        'pull' => 'right',
        'actions' => [
            [
                'url' => '/individuals/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye'
            ],
            [
                'open_modal' => '/individuals/edit/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'edit'
            ],
            [
                'open_modal' => '/individuals/delete/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'trash'
            ],
        ]
    ]
]);
echo '</div>';
?>
