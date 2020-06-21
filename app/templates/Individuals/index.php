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
                            'text' => __('Add individual'),
                            'class' => 'btn btn-primary',
                            'popover_url' => '/individuals/add'
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
                'onclick' => 'populateAndLoadModal(\'/individuals/edit/[onclick_params_data_path]\');',
                'onclick_params_data_path' => 'id',
                'icon' => 'edit'
            ],
            [
                'onclick' => 'populateAndLoadModal(\'/individuals/delete/[onclick_params_data_path]\');',
                'onclick_params_data_path' => 'id',
                'icon' => 'trash'
            ]
        ]
    ]
]);
echo '</div>';
?>
