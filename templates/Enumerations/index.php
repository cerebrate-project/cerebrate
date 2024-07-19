<?php

echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'data' => $data,
        'top_bar' => [
            'children' => [
                [
                    'type' => 'search',
                    'button' => __('Search'),
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
                'name' => __('Value'),
                'sort' => 'value',
                'data_path' => 'value',
            ]
        ],
        'title' => __('Enumerations Index'),
        'description' => null,
        'pull' => 'right',
        'actions' => [
            [
                'open_modal' => '/enumerations/delete/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'trash',
                'requirement' => !empty($loggedUser['role']['perm_community_admin'])
            ],
        ]
    ]
]);
echo '</div>';
?>
