<?php
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
                            'text' => __('Add organisation'),
                            'class' => 'btn btn-primary',
                            'popover_url' => '/organisations/add',
                            'requirement' => !empty($loggedUser['role']['perm_community_admin']),
                        ]
                    ]
                ],
                [
                    'type' => 'context_filters',
                    'context_filters' => $filteringContexts
                ],
                [
                    'type' => 'search',
                    'button' => __('Search'),
                    'placeholder' => __('Enter value to search'),
                    'data' => '',
                    'searchKey' => 'value',
                    'allowFilering' => true
                ],
                [
                    'type' => 'table_action',
                    'table_setting_id' => 'organisation_index',
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
                'sort' => 'name',
            ],
            [
                'name' => __('UUID'),
                'sort' => 'uuid',
                'class' => 'short',
                'data_path' => 'uuid',
            ],
            [
                'name' => __('Members'),
                'data_path' => 'alignments',
                'element' =>  'count_summary',
                'url' => '/individuals/index/?Organisations.id={{url_data}}',
                'url_data_path' => 'id'
            ],
            [
                'name' => __('Group memberships'),
                'data_path' => 'org_groups',
                'data_id_sub_path' => 'id',
                'data_value_sub_path' => 'name',
                'element' =>  'link_list',
                'url_pattern' => '/orgGroups/view/{{data_id}}'
            ],
            [
                'name' => __('URL'),
                'sort' => 'url',
                'class' => 'short',
                'data_path' => 'url',
            ],
            [
                'name' => __('Country'),
                'data_path' => 'nationality',
                'sort' => 'nationality',
            ],
            [
                'name' => __('Sector'),
                'data_path' => 'sector',
                'sort' => 'sector',
            ],
            [
                'name' => __('Type'),
                'data_path' => 'type',
                'sort' => 'type',
            ],
            [
                'name' => __('Tags'),
                'data_path' => 'tags',
                'element' => 'tags',
            ],
        ],
        'title' => __('ContactDB Organisation Index'),
        'description' => __('A list of organisations known by your Cerebrate instance. This list can get populated either directly, by adding new organisations or by fetching them from trusted remote sources.'),
        'includeAllPagination' => true,
        'actions' => [
            [
                'url' => '/organisations/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye'
            ],
            [
                'open_modal' => '/organisations/edit/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'edit',
                'complex_requirement' => [
                    'function' => function ($row, $options) use ($loggedUser, $validOrgs) {
                        if ($loggedUser['role']['perm_community_admin'] || ($loggedUser['role']['perm_org_admin'] && $row['id'] == $loggedUser['organisation']['id'])) {
                            return true;
                        }
                        if ($loggedUser['role']['perm_group_admin'] && in_array($row['id'], $validOrgs)) {
                            return true;
                        }
                        return false;
                    }
                ]
            ],
            [
                'open_modal' => '/organisations/delete/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'icon' => 'trash',
                'requirement' => $loggedUser['role']['perm_community_admin']
            ],
        ]
    ]
]);
echo '</div>';
?>
