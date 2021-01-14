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
                    'additionalUrlParams' => $brood_id . '/sharingGroups'
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
            ]
        ],
        'title' => __('Sharing Groups Index'),
        'pull' => 'right',
        'actions' => [
            [
                'url' => '/broods/downloadSharingGroup/' . $brood_id,
                'url_params_data_paths' => ['id'],
                'title' => __('Download'),
                'icon' => 'download'
            ]
        ]
    ]
]);
echo '</div>';
?>
