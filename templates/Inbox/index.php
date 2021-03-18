<?php
echo $this->Html->scriptBlock(sprintf(
    'var csrfToken = %s;',
    json_encode($this->request->getAttribute('csrfToken'))
));
echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'data' => $data,
        'top_bar' => [
            'children' => [
                [
                    'type' => 'context_filters',
                    'context_filters' => !empty($filteringContexts) ? $filteringContexts : []
                ],
                [
                    'type' => 'search',
                    'button' => __('Filter'),
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
                'data_path' => 'id',
            ],
            [
                'name' => 'created',
                'sort' => 'created',
                'data_path' => 'created',
                'element' => 'datetime'
            ],
            [
                'name' => 'scope',
                'sort' => 'scope',
                'data_path' => 'scope',
            ],
            [
                'name' => 'action',
                'sort' => 'action',
                'data_path' => 'action',
            ],
            [
                'name' => 'title',
                'sort' => 'title',
                'data_path' => 'title',
            ],
            [
                'name' => 'origin',
                'sort' => 'origin',
                'data_path' => 'origin',
            ],
            [
                'name' => 'user',
                'sort' => 'user_id',
                'data_path' => 'user',
                'element' => 'user'
            ],
            [
                'name' => 'description',
                'sort' => 'description',
                'data_path' => 'description',
            ],
            [
                'name' => 'comment',
                'sort' => 'comment',
                'data_path' => 'comment',
            ],
        ],
        'title' => __('Inbox'),
        'description' => __('A list of requests to be manually processed'),
        'actions' => [
            [
                'url' => '/inbox/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye',
                'title' => __('View request')
            ],
            [
                'open_modal' => '/inbox/process/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'cogs',
                'title' => __('Process request')
            ],
            [
                'open_modal' => '/inbox/delete/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'trash',
                'title' => __('Discard request')
            ],
        ]
    ]
]);
echo '</div>';
?>
