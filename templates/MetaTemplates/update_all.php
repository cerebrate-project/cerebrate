<?php

use Cake\Routing\Router;

$bodyHtml = '';
$modalType = 'confirm';
$modalSize = 'lg';

$tableHtml = '<table class="table"><thead><tr>';
$tableHtml .= sprintf('<th class="text-nowrap">%s</th>', __('ID'));
$tableHtml .= sprintf('<th class="text-nowrap">%s</th>', __('Template'));
$tableHtml .= sprintf('<th class="text-nowrap">%s</th>', __('Version'));
$tableHtml .= sprintf('<th class="text-nowrap">%s</th>', __('New Template'));
$tableHtml .= sprintf('<th class="text-nowrap">%s</th>', __('Update available'));
$tableHtml .= sprintf('<th class="text-nowrap">%s</th>', __('Has Conflicts'));
$tableHtml .= sprintf('<th class="text-nowrap">%s</th>', __('Will be updated'));
$tableHtml .= '</tr></thead><tbody>';
$numberOfUpdates = 0;
$numberOfSkippedUpdates = 0;
foreach ($templatesUpdateStatus as $uuid => $status) {
    $tableHtml .= '<tr>';
    if (!empty($status['new'])) {
        $tableHtml .= sprintf('<td>%s</td>', __('N/A'));
    } else {
        $tableHtml .= sprintf(
            '<td><a href="%s">%s</a></td>',
            Router::url(['controller' => 'MetaTemplates', 'action' => 'view', 'plugin' => null, h($status['existing_template']->id)]),
            h($status['existing_template']->id)
        );
    }
    if (!empty($status['new'])) {
        $tableHtml .= sprintf('<td>%s</td>', h($uuid));
    } else {
        $tableHtml .= sprintf(
            '<td><a href="%s">%s</a></td>',
            Router::url(['controller' => 'MetaTemplates', 'action' => 'view', 'plugin' => null, h($status['existing_template']->id)]),
            h($status['existing_template']->name)
        );
    }
    if (!empty($status['new'])) {
        $tableHtml .= sprintf('<td>%s</td>', __('N/A'));
    } else {
        if ($status['current_version'] == $status['next_version']) {
            $tableHtml .= sprintf(
                '<td>%s</td>',
                h($status['current_version'])
            );
        } else {
            $tableHtml .= sprintf(
                '<td>%s %s %s</td>',
                h($status['current_version']),
                $this->Bootstrap->icon('arrow-right', ['class' => 'fs-8']),
                h($status['next_version'])
            );
        }
    }
    if (!empty($status['new'])) {
        $numberOfUpdates += 1;
        $tableHtml .= sprintf('<td>%s</td>', $this->Bootstrap->icon('check'));
    } else {
        $tableHtml .= sprintf('<td>%s</td>', $this->Bootstrap->icon('times'));
    }
    if (!empty($status['new'])) {
        $tableHtml .= sprintf('<td>%s</td>', __('N/A'));
    } else {
        $tableHtml .= sprintf('<td>%s</td>', empty($status['up-to-date']) ? $this->Bootstrap->icon('check') : $this->Bootstrap->icon('times'));
    }
    if (!empty($status['new'])) {
        $tableHtml .= sprintf('<td>%s</td>', __('N/A'));
    } elseif (!empty($status['up-to-date'])) {
        $tableHtml .= sprintf('<td>%s</td>', __('N/A'));
    } else {
        $tableHtml .= sprintf('<td>%s</td>', !empty($status['conflicts']) ? $this->Bootstrap->icon('check') : $this->Bootstrap->icon('times'));
    }
    if (!empty($status['new'])) {
        $tableHtml .= sprintf('<td>%s</td>', $this->Bootstrap->icon('check', ['class' => 'text-success']));
    } else {
        // Depends on the strategy used by the update_all function. Right now, every update create a brand new template
        // leaving existing data untouched. So regardless of the conflict, the new template will be created
        if (!empty($status['new']) || empty($status['up-to-date'])) {
            $numberOfUpdates += 1;
            $tableHtml .= sprintf('<td>%s</td>', $this->Bootstrap->icon('check', ['class' => 'text-success']));
        } else {
            $numberOfSkippedUpdates += 1;
            $tableHtml .= sprintf('<td>%s</td>', $this->Bootstrap->icon('times', ['class' => 'text-danger']));
        }
    }
    $tableHtml .= '</tr>';
}
$tableHtml .= '</tbody></table>';

if (empty($numberOfSkippedUpdates) && empty($numberOfUpdates)) {
    $bodyHtml .= $this->Bootstrap->alert([
        'variant' => 'success',
        'text' => __('All meta-templates are already up-to-date!'),
        'dismissible' => false,
    ]);
    $modalType = 'ok-only';
} elseif ($numberOfSkippedUpdates == 0) {
    $bodyHtml .= $this->Bootstrap->alert([
        'variant' => 'success',
        'text' => __('All {0} meta-templates can be updated', $numberOfUpdates),
        'dismissible' => false,
    ]);
} else {
    $modalSize = 'xl';
    $alertHtml = '';
    if (!empty($numberOfUpdates)) {
        $alertHtml .= sprintf('<div>%s</div>', __('{0} meta-templates can be updated.', sprintf('<strong>%s</strong>', $numberOfUpdates)));
    }
    if (!empty($numberOfSkippedUpdates)) {
        $alertHtml .= sprintf('<div>%s</div>', __('{0} meta-templates will be skipped.', sprintf('<strong>%s</strong>', $numberOfSkippedUpdates)));
        $alertHtml .= sprintf('<div>%s</div>', __('You can still choose the update strategy when updating each conflicting template manually.'));
    }
    $bodyHtml .= $this->Bootstrap->alert([
        'variant' => 'warning',
        'html' => $alertHtml,
        'dismissible' => false,
    ]);
}

$bodyHtml .= $tableHtml;
$form = sprintf(
    '<div class="d-none">%s%s</div>',
    $this->Form->create(null),
    $this->Form->end()
);
$bodyHtml .= $form;

echo $this->Bootstrap->modal([
    'title' => h($title),
    'bodyHtml' => $bodyHtml,
    'size' => $modalSize,
    'type' => $modalType,
    'confirmText' => __('Update meta-templates'),
    'confirmFunction' => 'updateMetaTemplate',
]);
?>

<script>
    function updateMetaTemplate(modalObject, tmpApi) {
        const $form = modalObject.$modal.find('form')
        return tmpApi.postForm($form[0]).catch((errors) => {
            const formHelper = new FormValidationHelper($form[0])
            const errorHTMLNode = formHelper.buildValidationMessageNode(errors, true)
            modalObject.$modal.find('div.form-error-container').append(errorHTMLNode)
            return errors
        })
    }
</script>