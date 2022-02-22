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
]);
?>
