<?php
/*
 *  Toggle element - a simple checkbox with the current state selected
 *  On click, issues a GET to a given endpoint, retrieving a form with the
 *  value flipped, which is immediately POSTed.
 *  to fetch it.
 *
 */
    $data = $this->Hash->get($row, $field['data_path']);
    $seed = rand();
    $checkboxId = 'GenericToggle-' . $seed;
    $tempboxId = 'TempBox-' . $seed;

    $requirementMet = true;
    if (isset($field['toggle_data']['requirement'])) {
        if (isset($field['toggle_data']['requirement']['options']['datapath'])) {
            foreach ($field['toggle_data']['requirement']['options']['datapath'] as $name => $path) {
                $field['toggle_data']['requirement']['options']['datapath'][$name] = empty($this->Hash->extract($row, $path)[0]) ? null : $this->Hash->extract($row, $path)[0];
            }
        }
        $options = isset($field['toggle_data']['requirement']['options']) ? $field['toggle_data']['requirement']['options'] : array();
        $requirementMet = $field['toggle_data']['requirement']['function']($row, $options);
    }

    echo sprintf(
        '<input type="checkbox" id="%s" %s %s><span id="%s" class="d-none"></span>',
        $checkboxId,
        empty($data) ? '' : 'checked',
        $requirementMet ? '' : 'disabled="disabled"',
        $tempboxId
    );

    // inject title and body vars into their placeholder
    if (!empty($field['toggle_data']['confirm'])) {
        $availableConfirmOptions = ['enable', 'disable'];
        $confirmOptions = $field['toggle_data']['confirm'];
        foreach ($availableConfirmOptions as $optionType) {
            $availableType = ['title', 'titleHtml', 'body', 'bodyHtml'];
            foreach ($availableType as $varType) {
                if (!isset($confirmOptions[$optionType][$varType])) {
                    continue;
                }
                $confirmOptions[$optionType][$varType] = $this->StringFromPath->buildStringFromDataPath(
                    $confirmOptions[$optionType][$varType],
                    $row,
                    $confirmOptions[$optionType][$varType . '_vars'],
                    ['highlight' => true]
                );
            }
            if (!empty($confirmOptions[$optionType]['type'])) {
                if (!empty($confirmOptions[$optionType]['type']['function'])) {
                    $typeData = !empty($confirmOptions[$optionType]['type']['data']) ? $confirmOptions[$optionType]['type'] : [];
                    $confirmOptions[$optionType]['type'] = $confirmOptions[$optionType]['type']['function']($row, $typeData);
                }
            }
        }
    }
    $url = $this->StringFromPath->buildStringFromDataPath($field['url'], $row, $field['url_params_vars']);
?>

<?php if ($requirementMet): ?>
<script type="text/javascript">
(function() {
    const url = "<?= h($url) ?>"
    const confirmationOptions = <?= isset($confirmOptions) ? json_encode($confirmOptions) : 'false' ?>;
    $('#<?= $checkboxId ?>').click(function(evt) {
        evt.preventDefault()
        if(confirmationOptions !== false) {
            const correctOptions = $('#<?= $checkboxId ?>').prop('checked') ? confirmationOptions['enable'] : confirmationOptions['disable'] // Adjust modal option based on checkbox state
            const modalOptions = {
                ...correctOptions,
                APIConfirm: (tmpApi) => {
                    return submitForm(tmpApi, url)
                        .catch(e => {
                            // Provide feedback inside modal?
                        })
                },
            }
            UI.modal(modalOptions)
        } else {
            const tmpApi = new AJAXApi({
                statusNode: $('#<?= $checkboxId ?>')[0]
            })
            submitForm(tmpApi, url)
        }
    })

    function submitForm(api, url) {
        return api.fetchAndPostForm(url, {})
            .then(() => {
                UI.reload('/meta-templates', $('#table-container-<?= $tableRandomValue ?>'), $('#table-container-<?= $tableRandomValue ?> table.table'))
            })
    }
}())
</script>
<?php endif; ?>