// function saveHiddenColumns(table_setting_id, newTableSettings) {
function mergeAndSaveSettings(table_setting_id, newTableSettings) {
    const settingName = 'ui.table_setting'
    const urlGet = `/user-settings/getMySettingByName/${settingName}`
    AJAXApi.quickFetchJSON(urlGet).then(tableSettings => {
        tableSettings = JSON.parse(tableSettings.value)
        newTableSettings = mergeNewTableSettingsIntoOld(table_setting_id, tableSettings, newTableSettings)
        saveTableSetting(settingName, newTableSettings)
    }).catch((e) => { // setting probably doesn't exist
        saveTableSetting(settingName, newTableSettings)
    })
}

function mergeNewTableSettingsIntoOld(table_setting_id, oldTableSettings, newTableSettings) {
    // Merge recursively
    tableSettings = Object.assign({}, oldTableSettings, newTableSettings)
    tableSettings[table_setting_id] = Object.assign({}, oldTableSettings[table_setting_id], newTableSettings[table_setting_id])
    return tableSettings
}

function saveTableSetting(settingName, newTableSettings) {
    const urlSet = `/user-settings/setMySetting/${settingName}`
    AJAXApi.quickFetchAndPostForm(urlSet, {
        value: JSON.stringify(newTableSettings)
    }, {
        provideFeedback: false
    }).then(() => {
        UI.toast({
            variant: 'success',
            title: 'Table setting saved',
            delay: 3000
        })
    })
}