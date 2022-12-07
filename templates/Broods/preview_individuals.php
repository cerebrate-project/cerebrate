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
                            'text' => __('Download All'),
                            'popover_url' => sprintf('/broods/downloadIndividual/%s/all', h($brood_id)),
                        ]
                    ]
                ],
                [
                    'type' => 'search',
                    'button' => __('Search'),
                    'placeholder' => __('Enter value to search'),
                    'data' => '',
                    'searchKey' => 'value',
                    'additionalUrlParams' => $brood_id . '/individuals'
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
                'name' => __('Status'),
                'class' => 'short',
                'data_path' => 'status',
                'sort' => 'status',
                'element' => 'brood_sync_status',
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
                'scope' => 'organisation'
            ],
            [
                'name' => __('UUID'),
                'sort' => 'uuid',
                'data_path' => 'uuid',
                'placeholder' => __('Leave empty to auto generate')
            ],
        ],
        'title' => __('Individuals Index'),
        'pull' => 'right',
        'actions' => [
            [
                'open_modal' => '/broods/downloadIndividual/' . $brood_id . '/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'title' => __('Download'),
                'icon' => 'download'
            ]
        ]
    ]
]);
echo '</div>';
?>
