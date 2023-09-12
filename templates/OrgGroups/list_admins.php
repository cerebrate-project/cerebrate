<?php
echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'skip_pagination' => true,
        'data' => $data,
        'top_bar' => [
            'children' => [
                [
                    'type' => 'simple',
                    'children' => [
                        'data' => [
                            'type' => 'simple',
                            'text' => __('Add Group Administrator'),
                            'class' => 'btn btn-primary',
                            'popover_url' => '/orgGroups/assignAdmin/' . h($groupId),
                            'reload_url' => '/orgGroups/listAdmins/' . h($groupId),
                        ],
                    ],
                    'requirement' => $canEdit
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
                'name' => __('Username'),
                'class' => 'short',
                'data_path' => 'username',
                'sort' => 'username',
            ],
            [
                'name' => __('Email'),
                'class' => 'short',
                'data_path' => 'individual.email',
                'sort' => 'individual.email',
            ],
            [
                'name' => __('Organisation'),
                'sort' => 'organisation.name',
                'class' => 'short',
                'data_path' => 'organisation.name',
            ]
        ],
        'title' => null,
        'description' => null,
        'actions' => [
            [
                'open_modal' => '/orgGroups/removeAdmin/' . h($groupId) . '/[onclick_params_data_path]',
                'reload_url' => '/orgGroups/listAdmins/' . h($groupId),
                'modal_params_data_path' => 'id',
                'icon' => 'unlink',
                'title' => __('Remove the administrator from the group'),
                'requirement' => $canEdit
            ],
        ]
    ]
]);
echo '</div>';
?>
