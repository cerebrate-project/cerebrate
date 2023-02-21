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
                            'popover_url' => sprintf('/broods/downloadSharingGroup/%s/all', h($brood_id)),
                        ]
                    ]
                ],
                [
                    'type' => 'search',
                    'button' => __('Search'),
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
                'name' => __('Status'),
                'class' => 'short',
                'data_path' => 'status',
                'sort' => 'status',
                'element' => 'brood_sync_status',
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
                'open_modal' => '/broods/downloadSharingGroup/' . $brood_id . '/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'title' => __('Download'),
                'icon' => 'download'
            ]
        ]
    ]
]);
echo '</div>';
?>
