<?php
if (!empty($updateAvailables)) {
    $alertHtml = sprintf(
        '<h5 class="alert-heading">%s</h5>%s<div>%s</div>',
        __n('A new update is available!', 'New updates are available!', count($updateAvailables)),
        __('Updating to the latest version is highly recommanded.'),
        $this->Bootstrap->button([
            'variant' => 'success',
            'icon' => 'arrow-alt-circle-up',
            'class' => 'mt-1',
            'text' => __n('Run update', 'Run all updates', count($updateAvailables)),
            'onclick' => 'runAllUpdate()',
        ])
    );
    echo $this->Bootstrap->alert([
        'variant' => 'warning',
        'html' => $alertHtml,
        'dismissible' => false,
    ]);
}

foreach ($status as $i => &$update) {
    if ($update['status'] == 'up') {
        $update['_rowVariant'] = 'success';
    } else if ($update['status'] == 'down') {
        $update['_rowVariant'] = 'danger';
    }

    if (!empty($update['plugin'])) {
        $update['name'] = "{$update['plugin']}.{$update['name']}";
    }
}

echo $this->Bootstrap->table([], [
    'fields' => [
        ['path' => 'id', 'label' => __('ID')],
        ['path' => 'name', 'label' => __('Name')],
        ['path' => 'end_time', 'label' => __('End Time')],
        ['path' => 'time_taken_formated', 'label' => __('Time Taken')],
        ['path' => 'status', 'label' => __('Status')]
    ],
    'items' => $status,
]);
?>

<script>
function runAllUpdate() {
    const url = '/instance/migrate'
    const reloadUrl = '/instance/migrate-index'
    const modalOptions = {
        title: '<?= __n('Run database update?', 'Run all database updates?', count($updateAvailables)) ?>',
        body: '<?= __('The process might take some time.') ?>',
        type: 'confirm-success',
        confirmText: '<?= __n('Run update', 'Run all updates', count($updateAvailables)) ?>',
        APIConfirm: (tmpApi) => {
            return tmpApi.fetchAndPostForm(url, {}).then(() => {
                location.reload()
            })
        },
    }
    UI.modal(modalOptions)
}
</script>