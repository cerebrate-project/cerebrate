<?php

use Cake\Core\Configure;

echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'data' => $data,
        'top_bar' => [
            'children' => [
                [
                    'type' => 'multi_select_actions',
                    'children' => [
                        [
                            'text' => __('Mass Edit'),
                            'variant' => 'primary',
                            'onclick' => 'massEditUsers',
                        ]
                    ],
                    'data' => [
                        'id' => [
                            'value_path' => 'id'
                        ]
                    ]
                ],
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
                    'table_setting_id' => 'user_index',
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
                        'function' => function ($row, $options) {
                            return true;
                        },
                    ],
                    'skip_full_reload' => true,
                    'confirm' => [
                        'enable' => [
                            'titleHtml' => __('Confirm disabling the user?'),
                            'type' => 'confirm-warning',
                            'bodyHtml' => __('You\'re about to change the state of the user {{0}}.'),
                            'confirmText' => __('Disable user'),
                            'arguments' => [
                                'bodyHtml' => ['individual.email'],
                            ]
                        ],
                        'disable' => [
                            'titleHtml' => __('Confirm enabling the user?'),
                            'type' => 'confirm-success',
                            'bodyHtml' => __('You\'re about to change the state of the user {{0}}.'),
                            'confirmText' => __('Enable user'),
                            'arguments' => [
                                'bodyHtml' => ['individual.email'],
                            ]
                        ]
                    ]
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
                'name' => __('Administered Groups'),
                'data_path' => 'org_groups',
                'data_id_sub_path' => 'id',
                'data_value_sub_path' => 'name',
                'element' =>  'link_list',
                'url_pattern' => '/orgGroups/view/{{data_id}}'
            ],
            [
                'name' => __('Individual'),
                'description' => __('The individual associated with the user, as represented by the Individual\'s e-mail address.'),
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
                'name' => __('Country'),
                'sort' => 'organisation.nationality',
                'data_path' => 'organisation.nationality',
                'element' => 'country',
            ],
            [
                'name' => __('# User Settings'),
                'element' => 'count_summary',
                'data_path' => 'user_settings',
                'url' => '/user-settings/index?Users.id={{url_data}}',
                'url_data_path' => 'id'
            ],
            // [ // We might want to uncomment this at some point
            //     'name' => __('Keycloak status'),
            //     'element' => 'keycloak_status',
            //     'data_path' => 'keycloak_status',
            //     'requirements' => Configure::read('keycloak.enabled', false),
            // ],
        ],
        'title' => __('User index'),
        'description' => __('The list of enrolled users in this Cerebrate instance. All of the users have or at one point had access to the system.'),
        'includeAllPagination' => true,
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
                    'function' => function ($row, $options)  use ($loggedUser, $validRoles, $validOrgIDsFOrEdition) {
                        return $row['_canBeEdited'];
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
                    'function' => function ($row, $options) use ($loggedUser, $validRoles, $validOrgIDsFOrEdition) {
                        if (empty(Configure::read('user.allow-user-deletion'))) {
                            return false;
                        }
                        if ($row['id'] == $loggedUser['id']) {
                            return false;
                        }
                        return $row['_canBeEdited'];
                    }
                ]
            ],
        ]
    ]
]);
?>

<script>
    function massEditUsers(idList, selectedData, $table) {
        const successCallback = function([data, modalObject]) {
            UI.reload('/users/index', UI.getContainerForTable($table), $table)
        }
        const failCallback = ([data, modalObject]) => {
            const tableData = selectedData.map(row => {
                entryInError = data.filter(error => error.data.id == row.id)[0]
                $faIcon = $('<i class="fa"></i>').addClass(entryInError.success ? 'fa-check text-success' : 'fa-times text-danger')
                return [row.id, row.username, row.organisation.name, row.individual.name, role.name, entryInError.message, JSON.stringify(entryInError.errors), $faIcon]
            });
            handleMessageTable(
                modalObject.$modal,
                ['<?= __('ID') ?>', '<?= __('Username') ?>', '<?= __('Organisation') ?>', '<?= __('Individual') ?>', '<?= __('Role') ?>', '<?= __('Error') ?>', '<?= __('State') ?>'],
                tableData
            )
            const $footer = $(modalObject.ajaxApi.statusNode).parent()
            modalObject.ajaxApi.statusNode.remove()
            const $cancelButton = $footer.find('button[data-bs-dismiss="modal"]')
            $cancelButton.text('<?= __('OK') ?>').removeClass('btn-secondary').addClass('btn-primary')
        }
        UI.submissionModal('/users/massEdit', successCallback, failCallback).then(([modalObject, ajaxApi]) => {
            const $idsInput = modalObject.$modal.find('form').find('input#ids-field')
            $idsInput.val(JSON.stringify(idList))
            const tableData = selectedData.map(row => {
                return [row.id, row.username, row.organisation.name, row.individual.full_name, row.role.name]
            });
            handleMessageTable(
                modalObject.$modal,
                ['<?= __('ID') ?>', '<?= __('Username') ?>', '<?= __('Organisation') ?>', '<?= __('Individual') ?>', '<?= __('Role') ?>', ],
                tableData
            )
        })

        function constructMessageTable(header, data) {
            return HtmlHelper.table(
                header,
                data, {
                    small: true,
                    borderless: true,
                    tableClass: ['message-table', 'mt-4 mb-0'],
                }
            )
        }

        function handleMessageTable($modal, header, data) {
            const $modalBody = $modal.find('.modal-body')
            const $messageTable = $modalBody.find('table.message-table')
            const messageTableHTML = constructMessageTable(header, data)[0].outerHTML
            if ($messageTable.length) {
                $messageTable.html(messageTableHTML)
            } else {
                $modalBody.append(messageTableHTML)
            }
        }
    }
</script>