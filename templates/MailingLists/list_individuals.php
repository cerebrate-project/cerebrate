<?php
echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'data' => $individuals,
        'skip_pagination' => 1,
        'top_bar' => [
            'children' => [
                [
                    'type' => 'multi_select_actions',
                    'children' => [
                        [
                            'text' => __('Remove members'),
                            'variant' => 'danger',
                            'onclick' => 'removeMembers',
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
                            'text' => __('Add member'),
                            'popover_url' => '/mailingLists/addIndividual/' . h($mailing_list_id),
                            'reload_url' => '/mailingLists/listIndividuals/' . h($mailing_list_id)
                        ]
                    ]
                ],
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
                'name' => __('First name'),
                'data_path' => 'first_name',
            ],
            [
                'name' => __('Last name'),
                'data_path' => 'last_name',
            ],
            [
                'name' => __('Email'),
                'data_path' => 'email',
            ],
            [
                'name' => __('UUID'),
                'sort' => 'uuid',
                'data_path' => 'uuid',
            ]
        ],
        'actions' => [
            [
                'url' => '/individuals/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye'
            ],
            [
                'open_modal' => '/mailingLists/removeIndividual/' . h($mailing_list_id) . '/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'reload_url' => '/mailingLists/listIndividuals/' . h($mailing_list_id),
                'icon' => 'trash'
            ],
        ]
    ]
]);
?>

<script>
    function removeMembers(idList, selectedData, $table) {
        const successCallback = function([data, modalObject]) {
            UI.reload('/mailingLists/listIndividuals/<?= h($mailing_list_id) ?>', UI.getContainerForTable($table), $table)
        }
        const failCallback = ([data, modalObject]) => {
            const tableData = selectedData.map(row => {
                entryInError = data.filter(error => error.data.id == row.id)[0]
                $faIcon = $('<i class="fa"></i>').addClass(entryInError.success ? 'fa-check text-success' : 'fa-times text-danger')
                return [row.id, row.first_name, row.last_name, row.email, entryInError.message, JSON.stringify(entryInError.errors), $faIcon]
            });
            handleMessageTable(
                modalObject.$modal,
                ['<?= __('ID') ?>', '<?= __('First name') ?>', '<?= __('Last name') ?>', '<?= __('email') ?>', '<?= __('Message') ?>', '<?= __('Error') ?>', '<?= __('State') ?>'],
                tableData
            )
            const $footer = $(modalObject.ajaxApi.statusNode).parent()
            modalObject.ajaxApi.statusNode.remove()
            const $cancelButton = $footer.find('button[data-bs-dismiss="modal"]')
            $cancelButton.text('<?= __('OK') ?>').removeClass('btn-secondary').addClass('btn-primary')
        }
        UI.submissionModal('/mailingLists/removeIndividual/<?= h($mailing_list_id) ?>', successCallback, failCallback).then(([modalObject, ajaxApi]) => {
            const $idsInput = modalObject.$modal.find('form').find('input#ids-field')
            $idsInput.val(JSON.stringify(idList))
            const tableData = selectedData.map(row => {
                return [row.id, row.first_name, row.last_name, row.email]
            });
            handleMessageTable(
                modalObject.$modal,
                ['<?= __('ID') ?>', '<?= __('First name') ?>', '<?= __('Last name') ?>', '<?= __('email') ?>'],
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