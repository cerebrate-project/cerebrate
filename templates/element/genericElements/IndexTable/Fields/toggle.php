<?php
/*
 *  Toggle element - a simple checkbox with the current state selected
 *  On click, issues a GET to a given endpoint, retrieving a form with the
 *  value flipped, which is immediately POSTed.
 *  to fetch it.
 *  Options:
 *      - url: The URL on which to perform the POST
 *      - url_params_vars: Variables to be injected into the URL using the DataFromPath helper
 *      - toggle_data.skip_full_reload: If true, the index will not be reloaded and the checkbox will be flipped on success
 *      - toggle_data.editRequirement.function: A function to be called to assess if the checkbox can be toggled
 *      - toggle_data.editRequirement.options: Option that will be passed to the function
 *      - toggle_data.editRequirement.options.datapath: If provided, entries will have their datapath values converted into their extracted value
 *      - toggle_data.confirm.[enable/disable].title: 
 *      - toggle_data.confirm.[enable/disable].titleHtml: 
 *      - toggle_data.confirm.[enable/disable].body: 
 *      - toggle_data.confirm.[enable/disable].bodyHtml: 
 *      - toggle_data.confirm.[enable/disable].type: 
 *
 */
    $data = $this->Hash->get($row, $field['data_path']);
    $seed = rand();
    $checkboxId = 'GenericToggle-' . $seed;
    $tempboxId = 'TempBox-' . $seed;

    $requirementMet = false;
    if (isset($field['toggle_data']['editRequirement'])) {
        if (isset($field['toggle_data']['editRequirement']['options']['datapath'])) {
            foreach ($field['toggle_data']['editRequirement']['options']['datapath'] as $name => $path) {
                $field['toggle_data']['editRequirement']['options']['datapath'][$name] = empty($this->Hash->extract($row, $path)[0]) ? null : $this->Hash->extract($row, $path)[0];
            }
        }
        $options = isset($field['toggle_data']['editRequirement']['options']) ? $field['toggle_data']['editRequirement']['options'] : array();
        $requirementMet = $field['toggle_data']['editRequirement']['function']($row, $options);
    }

    echo sprintf(
        '<input type="checkbox" id="%s" %s %s><span id="%s" class="d-none"></span>',
        $checkboxId,
        empty($data) ? '' : 'checked',
        $requirementMet ? '' : 'disabled="disabled"',
        $tempboxId
    );

    // inject variables into the strings
    if (!empty($field['toggle_data']['confirm'])) {
        $instructions = [
            'enable.title' => 'enable.title_vars',
            'enable.titleHtml' => 'enable.titleHtml_vars',
            'enable.body' => 'enable.body_vars',
            'enable.bodyHtml' => 'enable.bodyHtml_vars',
            'enable.type' => 'enable.type',
            'disable.title' => 'disable.title_vars',
            'disable.titleHtml' => 'disable.titleHtml_vars',
            'disable.body' => 'disable.body_vars',
            'disable.bodyHtml' => 'disable.bodyHtml_vars',
            'disable.bodyHtml' => 'disable.bodyHtml_vars',
            'disable.type' => 'disable.type',
        ];
        $confirmOptions = $this->DataFromPath->buildStringsInArray($field['toggle_data']['confirm'], $row, $instructions, ['highlight' => true]);
    }
    $url = $this->DataFromPath->buildStringFromDataPath($field['url'], $row, $field['url_params_vars']);
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
        const reloadUrl = '<?= isset($field['toggle_data']['reload_url']) ? $field['toggle_data']['reload_url'] : $this->Url->build(['action' => 'index']) ?>'
        return api.fetchAndPostForm(url, {})
            .then(() => {
                <?php if (!empty($field['toggle_data']['skip_full_reload'])): ?>
                    const isChecked = $('#<?= $checkboxId ?>').prop('checked')
                    $('#<?= $checkboxId ?>').prop('checked', !$('#<?= $checkboxId ?>').prop('checked'))
                <?php else: ?>
                    UI.reload(reloadUrl, $('#table-container-<?= $tableRandomValue ?>'), $('#table-container-<?= $tableRandomValue ?> table.table'))
                <?php endif; ?>
            })
    }
}())
</script>
<?php endif; ?>