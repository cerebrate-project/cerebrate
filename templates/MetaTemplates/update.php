<?php

$bodyHtml = '';
$modalType = 'confirm';
$modalSize = 'lg';
if ($updateStatus['up-to-date']) {
    $bodyHtml .= $this->Bootstrap->alert([
        'variant' => 'success',
        'text' => __('This meta-template is already up-to-date!'),
        'dismissible' => false,
    ]);
    $modalType = 'ok-only';
} else {
    if ($updateStatus['automatically-updateable']) {
        $bodyHtml .= $this->Bootstrap->alert([
            'variant' => 'success',
            'html' => __('This meta-template can be updated to version {0} (current: {1}).', sprintf('<strong>%s</strong>', h($templateOnDisk['version'])), h($metaTemplate->version)),
            'dismissible' => false,
        ]);
        $form = $this->element('genericElements/Form/genericForm', [
            'entity' => null,
            'ajax' => false,
            'raw' => true,
            'data' => [
                'model' => 'MetaTemplate',
                'fields' => [
                    [
                        'field' => 'update_strategy',
                        'type' => 'checkbox',
                        'value' => 'update',
                        'checked' => true,
                    ]
                ],
                'submit' => [
                    'action' => $this->request->getParam('action')
                ],
            ]
        ]);
        $bodyHtml .= sprintf('<div class="d-none">%s</div>', $form);
    } else {
        $modalSize = 'xl';
        $bodyHtml .= $this->Bootstrap->alert([
            'variant' => 'warning',
            'text' => __('Updating to version {0} cannot be done automatically as it introduces some conflicts.', h($templateOnDisk['version'])),
            'dismissible' => false,
        ]);
        $conflictTable = $this->element('MetaTemplates/conflictTable', [
            'templateStatus' => $templateStatus,
            'metaTemplate' => $metaTemplate,
            'templateOnDisk' => $templateOnDisk,
        ]);
        $bodyHtml .= $this->Bootstrap->collapse([
            'title' => __('View conflicts'),
            'open' => false
        ], $conflictTable);
        $bodyHtml .= $this->element('MetaTemplates/conflictResolution', [
            'templateStatus' => $templateStatus,
            'metaTemplate' => $metaTemplate,
            'templateOnDisk' => $templateOnDisk,
        ]);
    }
}

echo $this->Bootstrap->modal([
    'title' => __('Update Meta Templates #{0} ?', h($metaTemplate->id)),
    'bodyHtml' => $bodyHtml,
    'size' => $modalSize,
    'type' => $modalType,
    'confirmText' => __('Update meta-templates'),
    // 'confirmFunction' => 'updateMetaTemplate',
]);
?>

<script>
    // function updateMetaTemplate(idList, selectedData, $table) {
    //     const successCallback = function([data, modalObject]) {
    //         location.reload()
    //     }
    //     const failCallback = ([data, modalObject]) => {
    //         const tableData = selectedData.map(row => {
    //             entryInError = data.filter(error => error.data.id == row.id)[0]
    //             $faIcon = $('<i class="fa"></i>').addClass(entryInError.success ? 'fa-check text-success' : 'fa-times text-danger')
    //             return [row.id, row.first_name, row.last_name, row.email, entryInError.message, JSON.stringify(entryInError.errors), $faIcon]
    //         });
    //         handleMessageTable(
    //             modalObject.$modal,
    //             ['<?= __('ID') ?>', '<?= __('First name') ?>', '<?= __('Last name') ?>', '<?= __('email') ?>', '<?= __('Message') ?>', '<?= __('Error') ?>', '<?= __('State') ?>'],
    //             tableData
    //         )
    //         const $footer = $(modalObject.ajaxApi.statusNode).parent()
    //         modalObject.ajaxApi.statusNode.remove()
    //         const $cancelButton = $footer.find('button[data-bs-dismiss="modal"]')
    //         $cancelButton.text('<?= __('OK') ?>').removeClass('btn-secondary').addClass('btn-primary')
    //     }
    //     UI.submissionModal('[URL_HERE]', successCallback, failCallback).then(([modalObject, ajaxApi]) => {
    //         const $idsInput = modalObject.$modal.find('form').find('input#ids-field')
    //         $idsInput.val(JSON.stringify(idList))
    //         const tableData = selectedData.map(row => {
    //             return [row.id, row.first_name, row.last_name, row.email]
    //         });
    //         handleMessageTable(
    //             modalObject.$modal,
    //             ['<?= __('ID') ?>', '<?= __('First name') ?>', '<?= __('Last name') ?>', '<?= __('email') ?>'],
    //             tableData
    //         )
    //     })

    //     function constructMessageTable(header, data) {
    //         return HtmlHelper.table(
    //             header,
    //             data, {
    //                 small: true,
    //                 borderless: true,
    //                 tableClass: ['message-table', 'mt-4 mb-0'],
    //             }
    //         )
    //     }

    //     function handleMessageTable($modal, header, data) {
    //         const $modalBody = $modal.find('.modal-body')
    //         const $messageTable = $modalBody.find('table.message-table')
    //         const messageTableHTML = constructMessageTable(header, data)[0].outerHTML
    //         if ($messageTable.length) {
    //             $messageTable.html(messageTableHTML)
    //         } else {
    //             $modalBody.append(messageTableHTML)
    //         }
    //     }
    // }
</script>