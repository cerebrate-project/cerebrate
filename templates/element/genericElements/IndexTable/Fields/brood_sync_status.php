<?php
$seed = 's-' . mt_rand();
$status = $this->Hash->extract($row, $field['data_path']);
$displayField = $this->Hash->get($row, $field['display_field_data_path']);

if ($status['local'] && $status['up_to_date']) {
    $variant = 'success';
    $text = __('Ok');
} else if ($status['local'] && !$status['up_to_date']) {
    $variant = 'warning';
    $text = __('Outdated');
} else {
    $variant = 'danger';
    $text = __('N/A');
}

echo $this->Bootstrap->badge([
    'id' => $seed,
    'variant' => $variant,
    'text' => $text,
    'icon' => ($status['local'] && !$status['up_to_date']) ? 'question-circle' : false,
    'title' => $status['title'],
    'class' => [
        (($status['local'] && !$status['up_to_date']) ? 'cursor-pointer' : ''),
    ],
]);
?>

<?php if ($status['local'] && !$status['up_to_date']) : ?>
    <script>
        $(document).ready(function() {

            function genTable(status) {
                status.forEach(function(row, i) {
                    status[i][1] = buildTableEntry(status[i][1])
                    status[i][2] = buildTableEntry(status[i][2])
                });
                const $table = HtmlHelper.table(
                    ['<?= __('Field name') ?>', '<?= __('Local value') ?>', '<?= __('Remote value') ?>'],
                    status, {
                        small: true,
                        caption: `${status.length} fields`,
                    }
                )

                const $container = $('<div>')
                const $header = $('<h4>').text('<?= __('Main fields') ?>')
                $container.append($header, $table)
                return $container[0].outerHTML
            }

            function genTableForMetafields(status) {
                let rearrangedStatus = []
                for (const [field, metafieldData] of Object.entries(status)) {
                    rearrangedChanges = []
                    const metaTemplate = metafieldData['meta_template']
                    const metafields = metafieldData['delta']
                    metafields.forEach(function(metaFields, i) {
                        const localMetafield = metaFields.local
                        const remoteMetafield = metaFields.remote
                        rearrangedChanges.push([
                            buildTableEntryForMetaField(localMetafield),
                            buildTableEntryForMetaField(remoteMetafield),
                        ])
                    })
                    const $changesTable = HtmlHelper.table(
                        null,
                        rearrangedChanges, {
                            small: true,
                            borderless: true,
                            striped: true,
                            fixed_layout: true,
                            tableClass: 'mb-0',
                        }
                    )
                    const $field = $('<td>')
                        .css('min-width', '8em')
                        .text(field)
                    const $template = $('<td>')
                        .css('min-width', '6em')
                        .append(
                            $('<span>').text(metaTemplate.name),
                            $('<sup>').text(`v${metaTemplate.version}`),
                        )
                    rearrangedStatus.push([
                        $template,
                        $field,
                        $('<td>').attr('colspan', 2).append($changesTable),
                    ])
                }
                const $container = $('<div>')
                const $header = $('<h4>').text('<?= __('Meta Fields') ?>')
                const metafieldAmount = Object.values(status).reduce(function(carry, metaFields) {
                    return carry + metaFields.length
                }, 0)
                const $table = HtmlHelper.table(
                    ['<?= __('Template') ?>', '<?= __('Field name') ?>', '<?= __('Local value') ?>', '<?= __('Remote value') ?>'],
                    // ['<?= __('Field name') ?>', '<?= __('Local value') ?>', '<?= __('Remote value') ?>'],
                    rearrangedStatus, {
                        small: true,
                        caption: `${metafieldAmount} meta-fields`,
                    }
                )
                $container.append($header, $table)
                return $container[0].outerHTML
            }

            function buildTableEntry(value) {
                let $elem
                if (typeof value === 'object') {
                    $elem = $('<span>')
                        .html(syntaxHighlightJson(value, 2))
                } else {
                    $elem = $('<pre>')
                        .text(value)
                }
                return $elem
            }

            function buildTableEntryForMetaField(metafieldDifferences) {
                if (metafieldDifferences !== null) {
                    const $container = $('<table>').addClass('table table-borderless table-xs mb-0')
                    for (const [field, value] of Object.entries(metafieldDifferences)) {
                        const $entry = $('<tr>')
                            .append(
                                $('<th>')
                                .addClass('fw-normal')
                                .text(field),
                                $('<td>')
                                .append(
                                    $('<pre>')
                                    .addClass('d-inline mb-0')
                                    .text(value)
                                )
                            )
                        $container.append($entry)
                    }
                    return $container
                }
                return $('<span>').html(syntaxHighlightJson(metafieldDifferences))
            }

            const status = <?= json_encode($status) ?>;
            $('#<?= $seed ?>')
                .data('sync-status', status)
                .click(function() {
                    const syncStatusData = $(this).data('sync-status')['data']
                    console.log(syncStatusData);
                    let rearrangedStatusData = []
                    for (const [field, values] of Object.entries(syncStatusData)) {
                        if (field !== 'meta_fields') {
                            rearrangedStatusData.push([
                                field,
                                values.local,
                                values.remote,
                            ])
                        }
                    }
                    const bodyHtml = genTable(rearrangedStatusData) +
                        (syncStatusData['meta_fields'] ? genTableForMetafields(syncStatusData['meta_fields']) : '')
                    const options = {
                        title: '<?= __('Difference with the remote for `{0}`', $displayField) ?>',
                        bodyHtml: bodyHtml,
                        type: 'ok-only',
                        size: 'xl',
                    }
                    UI.modal(options)
                })
        })
    </script>
<?php endif; ?>