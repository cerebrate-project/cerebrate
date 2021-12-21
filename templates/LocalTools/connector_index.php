<?php
$multiSelectActions = [];
foreach ($connector->getBatchActionFunctions() as $actionName => $actionData) {
    $multiSelectActions[] = [
        'text' => $actionData['ui']['text'],
        'icon' => $actionData['ui']['icon'],
        'variant' => $actionData['ui']['variant'],
        'params' => ['data-actionname' => $actionName],
        'onclick' => 'handleMultiSelectAction'
    ];
}

echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'data' => $data,
        'top_bar' => [
            'children' => [
                [
                    'type' => 'multi_select_actions',
                    'children' => $multiSelectActions,
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
                            'text' => __('Add connection'),
                            'popover_url' => sprintf('/localTools/add/%s', h($connectorName))
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
                'name' => __('Name'),
                'sort' => 'name',
                'data_path' => 'name',
            ],
            [
                'name' => __('Connector'),
                'sort' => 'connector',
                'data_path' => 'connector',
            ],
            [
                'name' => 'Exposed',
                'data_path' => 'exposed',
                'element' => 'boolean'
            ],
            [
                'name' => 'settings',
                'data_path' => 'settings',
                'isJson' => 1,
                'element' => 'array'
            ],
            [
                'name' => 'description',
                'data_path' => 'description'
            ],
            [
                'name' => 'health',
                'data_path' => 'health',
                'element' => 'health',
                'class' => 'text-nowrap'
            ]
        ],
        'title' => false,
        'description' => false,
        'actions' => [
            [
                'url' => '/localTools/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye'
            ],
            [
                'open_modal' => '/localTools/connectLocal/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'reload_url' => sprintf('/localTools/connectorIndex/%s', h($connectorName)),
                'icon' => 'plug'
            ],
            [
                'open_modal' => '/localTools/edit/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'reload_url' => sprintf('/localTools/connectorIndex/%s', h($connectorName)),
                'icon' => 'edit'
            ],
            [
                'open_modal' => '/localTools/delete/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'reload_url' => sprintf('/localTools/connectorIndex/%s', h($connectorName)),
                'icon' => 'trash'
            ],
        ]
    ]
]);
?>

<script>
    function handleMultiSelectAction(idList, selectedRows, $table, $clicked) {
        const url = `/localTools/batchAction/${$clicked.data('actionname')}?connection_ids=${encodeURIComponent(idList)}`
        const reloadUrl = '/localTools/connectorIndex/<?= $connectorName ?>'
        const successCallback = function([requestData, modalObject]) {
            includeResultInModal(requestData, modalObject)
            UI.reload(reloadUrl, UI.getContainerForTable($table), $table)
        }
        const failCallback = function([requestData, modalObject]) {
            includeResultInModal(requestData, modalObject)
        }
        UI.submissionModal(url, successCallback, failCallback, {closeOnSuccess: false})
    }

    function includeResultInModal(requestData, modalObject) {
        const resultsHaveErrors = checkResultsHaveErrors(requestData.data)
        let tableData = []
        let tableHeader = []
        if (resultsHaveErrors) {
            tableHeader = ['<?= __('Connection ID') ?>', '<?= __('Connection Name') ?>', '<?= __('Message') ?>', '<?= __('Error') ?>', '<?= __('Success') ?>', '<?= __('Result') ?>']
        } else {
            tableHeader = ['<?= __('Connection ID') ?>', '<?= __('Connection Name') ?>', '<?= __('Message') ?>', '<?= __('Success') ?>', '<?= __('Result') ?>']
        }
        for (const key in requestData.data) {
            if (Object.hasOwnProperty.call(requestData.data, key)) {
                const singleResult = requestData.data[key];
                $faIcon = $('<i class="fa"></i>').addClass(singleResult.success ? 'fa-check text-success' : 'fa-times text-danger')
                $jsonResult = $('<pre class="p-2 rounded mb-0" style="max-width: 400px; max-height: 300px;background: #eeeeee55;"></pre>').append(
                    $('<code></code>').text(JSON.stringify(singleResult.data, null, 4))
                )
                if (resultsHaveErrors) {
                    tableData.push([singleResult.connection.id, singleResult.connection.name, singleResult.message, JSON.stringify(singleResult.errors, null, 4), $faIcon, $jsonResult])
                } else {
                    tableData.push([singleResult.connection.id, singleResult.connection.name, singleResult.message, $faIcon, $jsonResult])
                }
            }
        }
        handleMessageTable(
            modalObject.$modal,
            tableHeader,
            tableData
        )
        const $footer = $(modalObject.ajaxApi.statusNode).parent()
        modalObject.ajaxApi.options.statusNode.remove()
        const $cancelButton = $footer.find('button[data-dismiss="modal"]')
        $cancelButton.text('<?= __('OK') ?>').removeClass('btn-secondary').addClass('btn-primary')
    }

    function constructMessageTable(header, data) {
        return HtmlHelper.table(
            header,
            data,
            {
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

    function checkResultsHaveErrors(result) {
        for (const key in result) {
            if (Object.hasOwnProperty.call(result, key)) {
                const singleResult = result[key];
                if(!singleResult.success) {
                    return true
                }
            }
        }
        return false
    }
</script>
