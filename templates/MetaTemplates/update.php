<?php
use App\Model\Table\MetaTemplatesTable;

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
        $bodyHtml .= $this->Bootstrap->alert([
            'variant' => 'success',
            'text' => __('All meta-fields will be migrated to the newest version.'),
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
                        'value' => MetaTemplatesTable::UPDATE_STRATEGY_UPDATE_EXISTING,
                        'checked' => true,
                    ],
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
            'html' => __('Updating to version {0} cannot be done automatically as it introduces some conflicts.', sprintf('<strong>%s</strong>', h($templateOnDisk['version']))),
            'dismissible' => false,
        ]);
        $conflictTable = $this->element('MetaTemplates/conflictTable', [
            'templateStatus' => $templateStatus,
            'metaTemplate' => $metaTemplate,
            'templateOnDisk' => $templateOnDisk,
        ]);
        $conflictCount = array_reduce($templateStatus['conflicts'], function ($carry, $conflict) {
            return $carry + count($conflict['conflictingEntities']);
        }, 0);
        $bodyHtml .= $this->Bootstrap->collapse([
            'text' => __('View conflicts ({0})', $conflictCount),
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
