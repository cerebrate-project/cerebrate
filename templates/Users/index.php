<?php

use Cake\Core\Configure;

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
                            'text' => __('Add User'),
                            'class' => 'btn btn-primary',
                            'popover_url' => '/users/add'
                        ]
                    ]
                ],
                [
                    'type' => 'search',
                    'button' => __('Search'),
                    'placeholder' => __('Enter value to search'),
                    'data' => '',
                    'searchKey' => 'value'
                ],
                [
                    'type' => 'table_action',
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
                'name' => __('Disabled'),
                'sort' => 'disabled',
                'data_path' => 'disabled',
                'element' => 'toggle',
                'url' => '/users/toggle/{{0}}',
                'url_params_vars' => ['id'],
                'toggle_data' => [
                    'editRequirement' => [
                        'function' => function($row, $options) {
                            return true;
                        },
                    ],
                    'skip_full_reload' => true
                ]
            ],
            [
                'name' => __('Username'),
                'sort' => 'username',
                'data_path' => 'username',
            ],
            [
                'name' => __('Organisation'),
                'sort' => 'organisation.name',
                'data_path' => 'organisation.name',
                'url' => '/organisations/view/{{0}}',
                'url_vars' => ['organisation.id']
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
            [
                'name' => __('# User Settings'),
                'element' => 'count_summary',
                'data_path' => 'user_settings',
                'url' => '/user-settings/index?Users.id={{url_data}}',
                'url_data_path' => 'id'
            ],
            // [
            //     'name' => __('Keycloak status'),
            //     'element' => 'keycloak_status',
            //     'data_path' => 'keycloak_status',
            //     'requirements' => Configure::read('keycloak.enabled', false),
            // ],
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
                'open_modal' => '/users/edit/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'edit',
                'complex_requirement' => [
                    'options' => [
                        'datapath' => [
                            'role_id' => 'role_id'
                        ]
                    ],
                    'function' => function ($row, $options)  use ($loggedUser, $validRoles) {
                        if (empty($loggedUser['role']['perm_admin'])) {
                            if (empty($loggedUser['role']['perm_org_admin'])) {
                                return false;
                            }
                            if (!isset($validRoles[$options['datapath']['role_id']])) {
                                return false;
                            }
                        }
                        return true;
                    }
                ]
            ],
            [
                'open_modal' => '/users/delete/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'trash',
                'complex_requirement' => [
                    'options' => [
                        'datapath' => [
                            'role_id' => 'role_id'
                        ]
                    ],
                    'function' => function ($row, $options)  use ($loggedUser, $validRoles) {
                        if (empty($loggedUser['role']['perm_admin'])) {
                            if (empty($loggedUser['role']['perm_org_admin'])) {
                                return false;
                            }
                            if (!isset($validRoles[$options['datapath']['role_id']])) {
                                return false;
                            }
                        }
                        return true;
                    }
                ]
            ],
        ]
    ]
]);
echo '</div>';
?>
