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
                            'text' => __('Add brood'),
                            'popover_url' => '/broods/add'
                        ]
                    ]
                ],
                [
                    'type' => 'context_filters',
                    'context_filters' => $filteringContexts
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
                'name' => __('Name'),
                'sort' => 'name',
                'data_path' => 'name',
            ],
            [
                'name' => 'Connection test',
                'data_path' => 'id',
                'element' => 'connection_test'
            ],
            [
                'name' => __('Url'),
                'sort' => 'url',
                'data_path' => 'url',
            ],
            [
                'name' => __('Description'),
                'data_path' => 'description',
            ],
            [
                'name' => __('Owner Organisation'),
                'sort' => 'organisation.name',
                'data_path' => 'organisation',
                'element' => 'org'
            ]
        ],
        'title' => __('Broods Index'),
        'description' => __('Cerebrate can connect to other Cerebrate instances to exchange trust information and to instrument interconnectivity between connected local tools. Each such Cerebrate instance with its connected tools is considered to be a brood.'),
        'pull' => 'right',
        'actions' => [
            [
                'url' => '/broods/view',
                'url_params_data_paths' => ['id'],
                'title' => __('View details'),
                'icon' => 'eye'
            ],
            [
                'url' => '/localTools/broodTools',
                'url_params_data_paths' => ['id'],
                'title' => __('List available local tools'),
                'icon' => 'wrench'
            ],
            [
                'open_modal' => '/broods/edit/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'edit'
            ],
            [
                'open_modal' => '/broods/delete/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'trash'
            ],
        ]
    ]
]);
echo '</div>';
?>
