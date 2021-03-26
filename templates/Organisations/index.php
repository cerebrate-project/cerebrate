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
                            'text' => __('Add organisation'),
                            'class' => 'btn btn-primary',
                            'popover_url' => '/organisations/add'
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
                'url' => '/individuals/index/?Organisations.id={{url_data}}',
                'url_data_path' => 'id'
            ],
            [
                'name' => __('URL'),
                'sort' => 'url',
                'class' => 'short',
                'data_path' => 'url',
            ],
            [
                'name' => __('Nationality'),
                'data_path' => 'nationality',
            ],
            [
                'name' => __('Sector'),
                'data_path' => 'sector',
            ],
            [
                'name' => __('Type'),
                'data_path' => 'type',
            ]
        ],
        'title' => __('ContactDB Organisation Index'),
        'description' => __('A list of organisations known by your Cerebrate instance. This list can get populated either directly, by adding new organisations or by fetching them from trusted remote sources.'),
        'pull' => 'right',
        'actions' => [
            [
                'url' => '/organisations/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye'
            ],
            [
                'open_modal' => '/organisations/edit/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'edit'
            ],
            [
                'open_modal' => '/organisations/delete/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'trash'
            ],
        ]
    ]
]);
echo '</div>';
?>
