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
                            'text' => __('Download All'),
                            'popover_url' => sprintf('/broods/downloadOrg/%s/all', h($brood_id)),
                        ]
                    ]
                ],
                [
                    'type' => 'search',
                    'button' => __('Search'),
                    'placeholder' => __('Enter value to search'),
                    'data' => '',
                    'searchKey' => 'value',
                    'additionalUrlParams' => $brood_id . '/organisations'
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
                'name' => __('Status'),
                'class' => 'short',
                'data_path' => 'status',
                'display_field_data_path' => 'name',
                'sort' => 'status',
                'element' => 'brood_sync_status',
            ],
            [
                'name' => __('Name'),
                'class' => 'short',
                'data_path' => 'name',
                'sort' => 'name'
            ],
            [
                'name' => __('UUID'),
                'class' => 'short',
                'data_path' => 'uuid',
            ],
            [
                'name' => __('URL'),
                'class' => 'short',
                'data_path' => 'url',
            ],
            [
                'name' => __('Nationality'),
                'data_path' => 'nationality',
                'sort' => 'nationality'
            ],
            [
                'name' => __('Sector'),
                'data_path' => 'sector',
                'sort' => 'sector'
            ],
            [
                'name' => __('Type'),
                'data_path' => 'type',
                'sort' => 'type'
            ]
        ],
        'title' => __('Organisation Index'),
        'pull' => 'right',
        'actions' => [
            [
                'open_modal' => '/broods/downloadOrg/' . $brood_id . '/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'title' => __('Download'),
                'icon' => 'download'
            ]
        ]
    ]
]);
echo '</div>';
?>
