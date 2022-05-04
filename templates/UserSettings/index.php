<?php
$title = __('User Setting index');
if (!empty($settingsForUser)) {
    $title .= __(' of {0}', $settingsForUser->username);
}

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
                            'text' => __('Add Setting'),
                            'class' => 'btn btn-primary',
                            'popover_url' => sprintf('/userSettings/add/%s', h($this->request->getQuery('Users_id')))
                        ]
                    ]
                ],
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
                'name' => __('User'),
                'sort' => 'user.username',
                'data_path' => 'user.username',
                'url' => '/users/view/{{0}}',
                'url_vars' => ['user.id']
            ],
            [
                'name' => __('Setting Name'),
                'sort' => 'name',
                'data_path' => 'name',
            ],
            [
                'name' => __('Setting Value'),
                'sort' => 'value',
                'data_path' => 'value',
                'class' => 'font-monospace'
            ],
        ],
        'title' => $title,
        'description' => __('The list of user settings in this Cerebrate instance. All users can have setting tied to their user profile.'),
        'actions' => [
            [
                'open_modal' => '/userSettings/edit/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'edit'
            ],
            [
                'open_modal' => '/userSettings/delete/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'trash'
            ],
        ]
    ]
]);
echo '</div>';
?>
