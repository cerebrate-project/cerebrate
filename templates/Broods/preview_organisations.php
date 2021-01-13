<?php
echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'data' => $data,
        'top_bar' => [
            'pull' => 'right',
            'children' => [
                [
                    'type' => 'search',
                    'button' => __('Filter'),
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
                'url' => '/broods/downloadOrg/' . $brood_id,
                'url_params_data_paths' => ['id'],
                'title' => __('Download'),
                'icon' => 'download'
            ]
        ]
    ]
]);
echo '</div>';
?>
