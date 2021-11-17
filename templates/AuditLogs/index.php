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
                'name' => __('IP'),
                'sort' => 'request_ip',
                'data_path' => 'request_ip',
            ],
            [
                'name' => __('Username'),
                'sort' => 'user.username',
                'data_path' => 'user.username',
            ],
            [
                'name' => __('Title'),
                'data_path' => 'title',
            ],
            [
                'name' => __('Model'),
                'sort' => 'model',
                'data_path' => 'model',
            ],
            [
                'name' => __('Model ID'),
                'sort' => 'model',
                'data_path' => 'model_id',
            ],
            [
                'name' => __('Action'),
                'sort' => 'action',
                'data_path' => 'action',
            ],
            [
                'name' => __('Change'),
                'sort' => 'change',
                'data_path' => 'change',
                'element' => 'json'
            ],
        ],
        'title' => __('Logs'),
        'description' => null,
        'pull' => 'right',
        'actions' => []
    ]
]);
echo '</div>';
?>
