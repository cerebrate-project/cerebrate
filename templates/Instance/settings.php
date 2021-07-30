<?php

$variantFromSeverity = [
    'critical' => 'danger',
    'warning' => 'warning',
    'info' => 'info',
];
$this->set('variantFromSeverity', $variantFromSeverity);
$settingTable = genNavcard($settingsProvider, $this);
?>

<script>
    const variantFromSeverity = <?= json_encode($variantFromSeverity) ?>;
    const settingsFlattened = <?= json_encode($settingsFlattened) ?>;
</script>

<div class="px-5">
    <div class="mb-3">
        <?=
            $this->element('Settings/search', [
            ]);
        ?>
    </div>
    <?= $settingTable; ?>
</div>

<?php
function genNavcard($settingsProvider, $appView)
{
    $cardContent = [];
    $cardNavs = array_keys($settingsProvider);
    foreach ($settingsProvider as $navName => $sectionSettings) {
        if (!empty($sectionSettings)) {
            $cardContent[] = genContentForNav($sectionSettings, $appView);
        } else {
            $cardContent[] = __('No Settings available yet');
        }
    }
    array_unshift($cardNavs, __('Settings Diagnostic'));
    $notice = $appView->element('Settings/notice', [
        'variantFromSeverity' => $appView->get('variantFromSeverity'),
    ]);
    array_unshift($cardContent, $notice);
    $tabsOptions0 = [
        // 'vertical' => true,
        // 'vertical-size' => 2,
        'card' => false,
        'pills' => false,
        'justify' => 'center',
        'nav-class' => ['settings-tabs'],
        'data' => [
            'navs' => $cardNavs,
            'content' => $cardContent
        ]
    ];
    $table0 = $appView->Bootstrap->tabs($tabsOptions0);
    return $table0;
}

function genContentForNav($sectionSettings, $appView)
{
    $groupedContent = [];
    $groupedSetting = [];
    foreach ($sectionSettings as $sectionName => $subSectionSettings) {
        if (!empty($subSectionSettings)) {
            $groupedContent[] = genSection($sectionName, $subSectionSettings, $appView);
        } else {
            $groupedContent[] = '';
        }
        if (!isLeaf($subSectionSettings)) {
            $groupedSetting[$sectionName] = array_filter( // only show grouped settings
                array_keys($subSectionSettings),
                function ($settingGroupName) use ($subSectionSettings) {
                    return !isLeaf($subSectionSettings[$settingGroupName]) && !empty($subSectionSettings[$settingGroupName]);
                }
            );
        }
    }
    $contentHtml = implode('', $groupedContent);
    $scrollspyNav = $appView->element('Settings/scrollspyNav', [
        'groupedSetting' => $groupedSetting
    ]);
    $mainPanelHeight = 'calc(100vh - 42px - 1rem - 56px - 38px - 1rem)';
    $container =  '<div class="d-flex">';
    $container .=   "<div class=\"\" style=\"flex: 0 0 10em;\">{$scrollspyNav}</div>";
    $container .=   "<div data-spy=\"scroll\" data-target=\"#navbar-scrollspy-setting\" data-offset=\"25\" style=\"height: {$mainPanelHeight}\" class=\"p-3 overflow-auto position-relative flex-grow-1\">{$contentHtml}</div>";
    $container .= '</div>';
    return $container;
}

function genSection($sectionName, $subSectionSettings, $appView)
{
    $sectionContent = [];
    $sectionContent[] = '<div>';
    $sectionContent[] = sprintf('<h2 id="%s">%s</h2>', getResolvableID($sectionName), h($sectionName));
    if (isLeaf($subSectionSettings)) {
        $panelHTML = $appView->element('Settings/panel', [
            'sectionName' => $sectionName,
            'panelName' => $sectionName,
            'panelSettings' => $subSectionSettings,
        ]);
        $sectionContent[] = $panelHTML;
    } else {
        foreach ($subSectionSettings as $panelName => $panelSettings) {
            if (!empty($panelSettings)) {
                $panelHTML = $appView->element('Settings/panel', [
                    'sectionName' => $sectionName,
                    'panelName' => $panelName,
                    'panelSettings' => $panelSettings,
                ]);
                $sectionContent[] = $panelHTML;
            } else {
                $sectionContent[] = '';
            }
        }
    }
    $sectionContent[] = '</div>';
    return implode('', $sectionContent);
}

function isLeaf($setting)
{
    return !empty($setting['name']) && !empty($setting['type']);
}

function getResolvableID($sectionName, $panelName=false)
{
    $id = sprintf('sp-%s', h($sectionName));
    if (!empty($panelName)) {
        $id .= '-' . preg_replace('/(\.|\s)/', '_', h($panelName));
    }
    return $id;
}
?>

<script>
    $(document).ready(function() {
        $('.depends-on-icon').tooltip({
            placement: 'right',
        })

        $('.settings-tabs a[data-toggle="tab"]').on('shown.bs.tab', function (event) {
            $('[data-spy="scroll"]').trigger('scroll.bs.scrollspy')
        })

        $('.tab-content input, .tab-content select').on('input', function() {
            if ($(this).attr('type') == 'checkbox') {
                const $input = $(this)
                const $inputGroup = $(this).closest('.form-group')
                const settingName = $(this).data('setting-name')
                const settingValue = $(this).is(':checked') ? 1 : 0
                saveSetting($inputGroup[0], $input, settingName, settingValue)
            } else {
                handleSettingValueChange($(this))
            }
        })

        $('.tab-content .input-group-actions .btn-save-setting').click(function() {
            const $input = $(this).closest('.input-group').find('input, select')
            const settingName = $input.data('setting-name')
            const settingValue = $input.val()
            saveSetting(this, $input, settingName, settingValue)
        })
        $('.tab-content .input-group-actions .btn-reset-setting').click(function() {
            const $btn = $(this)
            const $input = $btn.closest('.input-group').find('input, select')
            let oldValue = settingsFlattened[$input.data('setting-name')].value
            if ($input.is('select')) {
                oldValue = oldValue !== undefined ? oldValue : -1
            } else {
                oldValue = oldValue !== undefined ? oldValue : ''
            }
            $input.val(oldValue)
            handleSettingValueChange($input)
        })

        const referencedID = window.location.hash
        redirectToSetting(referencedID)
    })

    function saveSetting(statusNode, $input, settingName, settingValue) {
        const url = '/instance/saveSetting/'
        const data = {
            name: settingName,
            value: settingValue,
        }
        const APIOptions = {
            statusNode: statusNode,
        }
        AJAXApi.quickFetchAndPostForm(url, data, APIOptions).then((result) => {
            settingsFlattened[settingName] = result.data
            if ($input.attr('type') == 'checkbox') {
                $input.prop('checked', result.data.value)
            } else {
                $input.val(result.data.value)
            }
            handleSettingValueChange($input)
        }).catch((e) => {})
    }

    function handleSettingValueChange($input) {
        const oldValue = settingsFlattened[$input.data('setting-name')].value
        const newValue = ($input.attr('type') == 'checkbox' ? $input.is(':checked') : $input.val())
        if (newValue == oldValue || (newValue == '' && oldValue == undefined)) {
            restoreWarnings($input)
        } else {
            removeWarnings($input)
        }
    }

    function removeWarnings($input) {
        const $inputGroup = $input.closest('.input-group')
        const $inputGroupAppend = $inputGroup.find('.input-group-append')
        const $saveButton = $inputGroup.find('button.btn-save-setting')
        $input.removeClass(['is-invalid', 'border-warning', 'border-danger', 'border-info', 'warning', 'info'])
        $inputGroupAppend.removeClass('d-none')
        if ($input.is('select') && $input.find('option:selected').data('is-empty-option') == 1) {
            $inputGroupAppend.addClass('d-none') // hide save button if empty selection picked
        }
        $inputGroup.parent().find('.invalid-feedback').removeClass('d-block')
    }

    function restoreWarnings($input) {
        const $inputGroup = $input.closest('.input-group')
        const $inputGroupAppend = $inputGroup.find('.input-group-append')
        const $saveButton = $inputGroup.find('button.btn-save-setting')
        const setting = settingsFlattened[$input.data('setting-name')]
        if (setting.error) {
            borderVariant = setting.severity !== undefined ? variantFromSeverity[setting.severity] : 'warning'
            $input.addClass(['is-invalid', `border-${borderVariant}`, borderVariant])
            if (setting.severity == 'warning') {
                $input.addClass('warning')
            }
            $inputGroup.parent().find('.invalid-feedback').addClass('d-block').text(setting.errorMessage)
        } else {
            removeWarnings($input)
        }
        const $callout = $input.closest('.settings-group')
        updateCalloutColors($callout)
        $inputGroupAppend.addClass('d-none')
    }

    function updateCalloutColors($callout) {
        if ($callout.length == 0) {
            return
        }
        const $settings = $callout.find('input, select')
        const settingNames = Array.from($settings).map((i) => {
            return $(i).data('setting-name')
        })
        const severityMapping = {null: 0, info: 1, warning: 2, critical: 3}
        const severityMappingInverted = Object.assign({}, ...Object.entries(severityMapping).map(([k, v]) => ({[v]: k})))
        let highestSeverity = severityMapping[null]
        settingNames.forEach(name => {
            if (settingsFlattened[name].error) {
                highestSeverity = severityMapping[settingsFlattened[name].severity] > highestSeverity ? severityMapping[settingsFlattened[name].severity] : highestSeverity
            }
        });
        highestSeverity = severityMappingInverted[highestSeverity]
        $callout.removeClass(['callout', 'callout-danger', 'callout-warning', 'callout-info'])
        if (highestSeverity !== null) {
            $callout.addClass(['callout', `callout-${variantFromSeverity[highestSeverity]}`])
        }
    }

    function redirectToSetting(referencedID) {
        const $settingToFocus = $(referencedID)
        const pageNavID = $(referencedID).closest('.tab-pane').attr('aria-labelledby')
        const $navController = $(`#${pageNavID}`)
        $navController
            .on('shown.bs.tab.after-redirect', () => {
                $settingToFocus[0].scrollIntoView()
                const inputID = $settingToFocus.parent().attr('for')
                $settingToFocus.closest('.form-group').find(`#${inputID}`).focus()
                $navController.off('shown.bs.tab.after-redirect')
            })
            .tab('show')
    }
</script>

<style>
    .input-group-actions {
        z-index: 5;
    }
    a.btn-reset-setting {
        left: -1.25em;
    }
    .custom-select ~ div > a.btn-reset-setting {
        left: -2.5em;
    }
    .form-control[type="number"] ~ div > a.btn-reset-setting {
        left: -3em;
    }
</style>