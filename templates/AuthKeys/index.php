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
                            'text' => __('Add authentication key'),
                            'class' => 'btn btn-primary',
                            'popover_url' => '/authKeys/add'
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
                'name' => __('User'),
                'sort' => 'user.username',
                'data_path' => 'user.username',
            ],
            [
                'name' => __('Auth key'),
                'sort' => 'authkey',
                'data_path' => 'authkey',
                'privacy' => 1
            ]
        ],
        'title' => __('Authentication key Index'),
        'description' => __('A list of API keys bound to a user.'),
        'pull' => 'right',
        'actions' => [
            [
                'onclick' => 'populateAndLoadModal(\'/encryptionKeys/delete/[onclick_params_data_path]\');',
                'onclick_params_data_path' => 'id',
                'icon' => 'trash'
            ]
        ]
    ]
]);
