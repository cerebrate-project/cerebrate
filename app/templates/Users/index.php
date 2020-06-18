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
                'name' => __('Email'),
                'sort' => 'individual.email',
                'data_path' => 'individual.email',
                'url' => '/individuals/view/{{0}}',
                'url_vars' => ['individual.id']
            ],
            [
                'name' => __('First Name'),
                'sort' => 'individual.first_name',
                'data_path' => 'individual.first_name',
            ],
            [
                'name' => __('Last Name'),
                'sort' => 'individual.last_name',
                'data_path' => 'individual.last_name'
            ],
            [
                'name' => __('Role'),
                'sort' => 'role.name',
                'data_path' => 'role.name',
                'url' => '/roles/view/{{0}}',
                'url_vars' => ['role.id']
            ],
        ],
        'title' => __('User index'),
        'description' => __('The list of enrolled users in this Cerebrate instance. All of the users have or at one point had access to the system.'),
        'pull' => 'right',
        'actions' => [
            [
                'url' => '/users/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye'
            ],
            [
                'url' => '/users/edit',
                'url_params_data_paths' => ['id'],
                'icon' => 'edit'
            ],
            [
                'onclick' => 'populateAndLoadModal(\'/users/delete/[onclick_params_data_path]\');',
                'onclick_params_data_path' => 'id',
                'icon' => 'trash'
            ]
        ]
    ]
]);
echo '</div>';
?>
