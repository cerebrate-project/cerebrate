<?php
$tableSettings['hidden_column'] = $tableSettings['hidden_column'] ?? [];

$availableColumnsHtml = '';
$availableColumns = [];
foreach ($table_data['fields'] as $field) {
    $fieldName = !empty($field['name']) ? $field['name'] : \Cake\Utility\Inflector::humanize($field['data_path']);
    $isVisible = !in_array(h(\Cake\Utility\Inflector::variable($fieldName)), $tableSettings['hidden_column']);
    $availableColumns[] = $fieldName;
    $availableColumnsHtml .= sprintf(
        '<div class="form-check">
            <input class="form-check-input" type="checkbox" value="" id="columnCheck-%s" data-columnname="%s" %s>
            <label class="form-check-label w-100" for="columnCheck-%s">
                %s
            </label>
        </div>',
        h(\Cake\Utility\Inflector::variable($fieldName)),
        h(\Cake\Utility\Inflector::variable($fieldName)),
        $isVisible ? 'checked' : '',
        h(\Cake\Utility\Inflector::variable($fieldName)),
        h($fieldName)
    );
}

$availableColumnsHtml = $this->Bootstrap->genNode('form', [
    'class' => ['visible-column-form', 'px-2 py-1'],
], $availableColumnsHtml);
echo $availableColumnsHtml;
?>

<script>
    const debouncedHiddenColumnSaver = debounce(saveHiddenColumns, 2000)
    $('form.visible-column-form').find('input').change(function() {
        const $dropdownMenu = $(this).closest(`[data-table-random-value]`)
        const tableRandomValue = $dropdownMenu.attr('data-table-random-value')
        const $container = $dropdownMenu.closest('div[id^="table-container-"]')
        const $table = $container.find(`table[data-table-random-value="${tableRandomValue}"]`)
        const table_setting_id = $dropdownMenu.data('table_setting_id');
        toggleColumn(this.getAttribute('data-columnname'), this.checked, $table)
        tableSettings = {}
        tableSettings[table_setting_id] = genTableSettings($container)
        debouncedHiddenColumnSaver(table_setting_id, tableSettings)
    })

    function toggleColumn(columnName, isVisible, $table) {
        if (isVisible) {
            $table.find(`th[data-columnname="${columnName}"],td[data-columnname="${columnName}"]`).show()
        } else {
            $table.find(`th[data-columnname="${columnName}"],td[data-columnname="${columnName}"]`).hide()
        }
    }

    function saveHiddenColumns(table_setting_id, newTableSettings) {
        const settingName = 'ui.table_setting'
        const urlGet = `/user-settings/getSettingByName/${settingName}`
        AJAXApi.quickFetchJSON(urlGet).then(tableSettings => {
            tableSettings = JSON.parse(tableSettings.value)
            newTableSettings = mergeNewTableSettingsIntoOld(table_setting_id, tableSettings, newTableSettings)
            saveTableSetting(settingName, newTableSettings)
        }).catch((e) => { // setting probably doesn't exist
            saveTableSetting(settingName, newTableSettings)
        })
    }

    function genTableSettings($container) {
        let tableSetting = {};
        const $hiddenColumns = $container.find('form.visible-column-form').find('input').not(':checked')
        const hiddenColumns = Array.from($hiddenColumns.map(function() {
            return $(this).data('columnname')
        }))
        tableSetting['hidden_column'] = hiddenColumns
        return tableSetting
    }

    function mergeNewTableSettingsIntoOld(table_setting_id, oldTableSettings, newTableSettings) {
        tableSettings = Object.assign(oldTableSettings, newTableSettings)
        return tableSettings
    }

    function saveTableSetting(settingName, newTableSettings) {
        const urlSet = `/user-settings/setSetting/${settingName}`
        AJAXApi.quickFetchAndPostForm(urlSet, {
            value: JSON.stringify(newTableSettings)
        }, {
            provideFeedback: false
        })
    }

    $(document).ready(function() {
        const $form = $('form.visible-column-form')
        const $checkboxes = $form.find('input').not(':checked')
        const $dropdownMenu = $form.closest('.dropdown')
        const tableRandomValue = $dropdownMenu.attr('data-table-random-value')
        const $container = $dropdownMenu.closest('div[id^="table-container-"]')
        const $table = $container.find(`table[data-table-random-value="${tableRandomValue}"]`)
        $checkboxes.each(function() {
            toggleColumn(this.getAttribute('data-columnname'), this.checked, $table)
        })
    })
</script>