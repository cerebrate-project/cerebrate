<?php
echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'data' => $data,
        'top_bar' => [
            'children' => [
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
                'name' => __('Name'),
                'sort' => 'name',
                'data_path' => 'name',
            ],
            [
                'name' => __('Connector'),
                'sort' => 'connector',
                'data_path' => 'connector',
            ],
            [
                'name' => 'settings',
                'data_path' => 'settings',
                'isJson' => 1,
                'element' => 'array'
            ],
            [
                'name' => 'health',
                'data_path' => 'health',
                'element' => 'health',
                'class' => 'text-nowrap'
            ]
        ],
        'title' => false,
        'description' => false,
        'pull' => 'right',
        'actions' => [
            [
                'url' => '/localTools/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye'
            ],
            [
                'open_modal' => '/localTools/edit/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'edit'
            ],
            [
                'open_modal' => '/localTools/delete/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'trash'
            ],
        ]
    ]
]);
echo '</div>';
?>
