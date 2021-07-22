<?php
// debug($settings);
// debug($settingsProvider);
// debug($notices);
$mainNoticeHeading = [
    'critical' => __('Your Cerebrate instance requires immediate attention.'),
    'warning' => __('Issues found, it is recommended that you resolve them.'),
    'info' => __('There are some optional settings that are incorrect or not set.'),
];
$noticeDescriptionPerLevel = [
    'critical' => __('Cerebrate will not operate correctly or will be unsecure until these issues are resolved.'),
    'warning' => __('Some of the features of Cerebrate cannot be utilised until these issues are resolved.'),
    'info' => __('There are some optional tweaks that could be done to improve the looks of your Cerebrate instance.'),
];
$headingPerLevel = [
    'critical' => __('Critical settings'),
    'warning' => __('Warning settings'),
    'info' => __('Info settings'),
];
$variantFromSeverity = [
    'critical' => 'danger',
    'warning' => 'warning',
    'info' => 'info',
];
$this->set('variantFromSeverity', $variantFromSeverity);

$alertVariant = 'info';
$alertBody = '';
$skipHeading = false;
$tableItems = [];
foreach (array_keys($mainNoticeHeading) as $level) {
    if(!empty($notices[$level])) {
        $variant = $variantFromSeverity[$level];
        if (!$skipHeading) {
            $alertBody .= sprintf('<h5 class="alert-heading">%s</h5>', $mainNoticeHeading[$level]);
            $alertVariant = $variant;
            $skipHeading = true;
        }
        $tableItems[] = [
            'severity' => $headingPerLevel[$level],
            'issues' => count($notices[$level]),
            'badge-variant' => $variant,
            'description' => $noticeDescriptionPerLevel[$level],
        ];
    }
}
$alertBody .= $this->Bootstrap->table([
    'small' => true,
    'striped' => false,
    'hover' => false,
    'borderless' => true,
    'bordered' => false,
    'tableClass' => 'mb-0'
], [
    'fields' => [
        ['key' => 'severity', 'label' => __('Severity')],
        ['key' => 'issues', 'label' => __('Issues'), 'formatter' => function($count, $row) {
            return $this->Bootstrap->badge([
                'variant' => $row['badge-variant'],
                'text' => $count
            ]);
        }],
        ['key' => 'description', 'label' => __('Description')]
    ],
    'items' => $tableItems,
]);
$settingNotice = $this->Bootstrap->alert([
    'dismissible' => false,
    'variant' => $alertVariant,
    'html' => $alertBody
]);
$settingNotice = sprintf('<div class="mt-3">%s</div>', $settingNotice);
$this->set('settingNotice', $settingNotice);
$settingTable = genLevel0($settingsProvider, $this);
?>
<div class="px-5">
    <div class="mb-3">
        <select id="search-settings" class="d-block w-100" aria-describedby="<?= __('Search setting input') ?>"><option></option></select>
    </div>
    <?= $settingTable; ?>
</div>

<?php
function genLevel0($settingsProvider, $appView)
{
    $content0 = [];
    $level0 = array_keys($settingsProvider);
    foreach ($settingsProvider as $level1Name => $level1Setting) {
        if (!empty($level1Setting)) {
            $content0[] = genLevel1($level1Setting, $appView);
        } else {
            $content0[] = __('No Settings available yet');
        }
    }
    array_unshift($level0, __('Settings Diagnostic'));
    array_unshift($content0, $appView->get('settingNotice'));
    $tabsOptions0 = [
        // 'vertical' => true,
        // 'vertical-size' => 2,
        'card' => false,
        'pills' => false,
        'justify' => 'center',
        'nav-class' => ['settings-tabs'],
        'data' => [
            'navs' => $level0,
            'content' => $content0
        ]
    ];
    $table0 = $appView->Bootstrap->tabs($tabsOptions0);
    return $table0;
}

function genLevel1($level1Setting, $appView)
{
    $content1 = [];
    $nav1 = [];
    foreach ($level1Setting as $level2Name => $level2Setting) {
        if (!empty($level2Setting)) {
            $content1[] = genLevel2($level2Name, $level2Setting, $appView);
        } else {
            $content1[] = '';
        }
        $nav1[$level2Name] = array_filter( // only show grouped settings
            array_keys($level2Setting),
            function ($settingGroupName) use ($level2Setting) {
                return !isLeaf($level2Setting[$settingGroupName]) && !empty($level2Setting[$settingGroupName]);
            }
        );
    }
    $contentHtml = implode('', $content1);
    $scrollspyNav = genScrollspyNav($nav1);
    $mainPanelHeight = 'calc(100vh - 42px - 1rem - 56px - 38px - 1rem)';
    $container =  '<div class="d-flex">';
    $container .=   "<div class=\"\" style=\"flex: 0 0 10em;\">{$scrollspyNav}</div>";
    $container .=   "<div data-spy=\"scroll\" data-target=\"#navbar-scrollspy-setting\" data-offset=\"25\" style=\"height: {$mainPanelHeight}\" class=\"p-3 overflow-auto position-relative flex-grow-1\">{$contentHtml}</div>";
    $container .= '</div>';
    return $container;
}

function genLevel2($level2Name, $level2Setting, $appView)
{
    foreach ($level2Setting as $level3Name => $level3Setting) {
        if (!empty($level3Setting)) {
            $level3 = genLevel3($level2Name, $level3Name, $level3Setting, $appView);
            $content2[] = sprintf('<div id="%s">%s</div>', sprintf('sp-%s', h($level2Name)), $level3);
        } else {
            $content2[] = '';
        }
    }
    return implode('', $content2);
}

function genLevel3($level2Name, $settingGroupName, $setting, $appView)
{
    $settingGroup = '';
    if (isLeaf($setting)) {
        $tmp = genSingleSetting($settingGroupName, $setting, $appView);
        $settingGroup = "<div>{$tmp}</div>";
    } else {
        $tmpID = sprintf('sp-%s-%s', h($level2Name), h($settingGroupName)); 
        $settingGroup .= sprintf('<h4 id="%s"><a class="text-reset text-decoration-none" href="#%s">%s</a></h4>', $tmpID, $tmpID, h($settingGroupName));
        $groupIssueSeverity = false;
        foreach ($setting as $singleSettingName => $singleSetting) {
            $tmp = genSingleSetting($singleSettingName, $singleSetting, $appView);
            $settingGroup .= sprintf('<div class="ml-3">%s</div>', $tmp);
            if (!empty($singleSetting['error'])) {
                $settingVariant = $appView->get('variantFromSeverity')[$singleSetting['severity']];
                if ($groupIssueSeverity != 'danger') {
                    if ($groupIssueSeverity != 'warning') {
                        $groupIssueSeverity = $settingVariant;
                    }
                }
            }
        }
        $settingGroup = $appView->Bootstrap->genNode('div', [
            'class' => [
                'shadow',
                'p-2',
                'mb-4',
                'rounded',
                'settings-group',
                (!empty($groupIssueSeverity) ? "callout callout-${groupIssueSeverity}" : ''),
                ($appView->get('darkMode') ? 'bg-dark' : 'bg-light')
            ],
        ], $settingGroup);
    }
    return $settingGroup;
}

function genSingleSetting($settingName, $setting, $appView)
{
    $dependsOnHtml = '';
    if (!empty($setting['dependsOn'])) {
        $dependsOnHtml = $appView->Bootstrap->genNode('span', [
        ], $appView->Bootstrap->genNode('sup', [
            'class' => [
                $appView->FontAwesome->getClass('info'),
                'ml-1',
            ],
            'title' => __('This setting depends on the validity of: {0}', h($setting['dependsOn']))
        ]));
    }
    $settingId = str_replace('.', '_', $settingName);
    $label = $appView->Bootstrap->genNode('label', [
        'class' => ['font-weight-bolder', 'mb-0'],
        'for' => $settingId
    ], h($setting['name']) . $dependsOnHtml);
    $description = '';
    if (!empty($setting['description'])) {
        $description = $appView->Bootstrap->genNode('small', [
            'class' => ['form-text', 'text-muted', 'mt-0'],
            'id' => "{$settingId}Help"
        ], h($setting['description']));
    }
    $textColor = 'text-warning';
    if (!empty($setting['severity'])) {
        $textColor = "text-{$appView->get('variantFromSeverity')[$setting['severity']]}";
    }
    $error = $appView->Bootstrap->genNode('div', [
        'class' => ['d-block', 'invalid-feedback', $textColor],
    ], (!empty($setting['error']) ? h($setting['errorMessage']) : ''));
    if (empty($setting['type'])) {
        $setting['type'] = 'string';
    }
    if ($setting['type'] == 'string') {
        $input = genInputString($settingName, $setting, $appView);
    } elseif ($setting['type'] == 'boolean') {
        $input = genInputCheckbox($settingName, $setting, $appView);
        $description = '';
    } elseif ($setting['type'] == 'integer') {
        $input = genInputInteger($settingName, $setting, $appView);
    } elseif ($setting['type'] == 'select') {
        $input = genInputSelect($settingName, $setting, $appView);
    } elseif ($setting['type'] == 'multi-select') {
        $input = genInputMultiSelect($settingName, $setting, $appView);
    } else {
        $input = genInputString($settingName, $setting, $appView);
    }

    $inputGroupSave = $appView->Bootstrap->genNode('div', [
        'class' => ['input-group-append', 'd-none', 'position-relative', 'input-group-actions'],
    ], implode('', [
            $appView->Bootstrap->genNode('a', [
                'class' => ['position-absolute', 'fas fa-times', 'p-abs-center-y', 'text-reset text-decoration-none', 'btn-reset-setting'],
                'href' => '#',
                'style' => 'left: -1.25em; z-index: 5;'
            ]),
            $appView->Bootstrap->genNode('button', [
                'class' => ['btn', 'btn-success', 'btn-save-setting'],
                'type' => 'button',
                'style' => 'z-index: 5;'
            ], __('save')),
    ]));
    $inputGroup = $appView->Bootstrap->genNode('div', [
        'class' => ['input-group'],
    ], implode('', [$input, $inputGroupSave]));

    $container = $appView->Bootstrap->genNode('div', [
        'class' => ['form-group', 'mb-2']
    ], implode('', [$label, $inputGroup, $description, $error]));
    return $container;
}

function genInputString($settingName, $setting, $appView)
{
    $settingId = str_replace('.', '_', $settingName);
    return $appView->Bootstrap->genNode('input', [
        'class' => [
            'form-control',
            'pr-4',
            (!empty($setting['error']) ? 'is-invalid' : ''),
            (!empty($setting['error']) ? "border-{$appView->get('variantFromSeverity')[$setting['severity']]}" : ''),
            (!empty($setting['error']) && $setting['severity'] == 'warning' ? 'warning' : ''),
        ],
        'type' => 'text',
        'id' => $settingId,
        'data-setting-name' => $settingName,
        'value' => isset($setting['value']) ? $setting['value'] : "",
        'placeholder' => $setting['default'] ?? '',
        'aria-describedby' => "{$settingId}Help"
    ]);
}
function genInputCheckbox($settingName, $setting, $appView)
{
    $settingId = str_replace('.', '_', $settingName);
    $switch = $appView->Bootstrap->genNode('input', [
        'class' => [
            'custom-control-input',
            (!empty($setting['error']) ? 'is-invalid' : ''),
            (!empty($setting['error']) && $setting['severity'] == 'warning' ? 'warning' : ''),
        ],
        'type' => 'checkbox',
        'value' => !empty($setting['value']) ? 1 : 0,
        (!empty($setting['value']) ? 'checked' : '') => !empty($setting['value']) ? 'checked' : '',
        'id' => $settingId,
        'data-setting-name' => $settingName,
    ]);
    $label = $appView->Bootstrap->genNode('label', [
        'class' => [
            'custom-control-label'
        ],
        'for' => $settingId,
    ], h($setting['description']));
    $container = $appView->Bootstrap->genNode('div', [
        'class' => [
            'custom-control',
            'custom-switch',
        ],
    ], implode('', [$switch, $label]));
    return $container;
}
function genInputInteger($settingName, $setting, $appView)
{
    $settingId = str_replace('.', '_', $settingName);
    return $appView->Bootstrap->genNode('input', [
        'class' => [
            'form-control'
        ],
        'type' => 'number',
        'min' => '0',
        'step' => 1,
        'id' => $settingId,
        'data-setting-name' => $settingName,
        'aria-describedby' => "{$settingId}Help"
    ]);
}
function genInputSelect($settingId, $setting, $appView)
{
}
function genInputMultiSelect($settingId, $setting, $appView)
{
}

function genScrollspyNav($nav1)
{
    $nav =  '<nav id="navbar-scrollspy-setting" class="navbar">';
    $nav .=     '<nav class="nav nav-pills flex-column">';
    foreach ($nav1 as $group => $sections) {
        $nav .= sprintf('<a class="nav-link main-group text-reset p-1" href="#%s">%s</a>', sprintf('sp-%s', h($group)), h($group));
        $nav .= sprintf('<nav class="nav nav-pills sub-group collapse flex-column" data-maingroup="%s">', sprintf('sp-%s', h($group)));
        foreach ($sections as $section) {
            $nav .= sprintf('<a class="nav-link nav-link-group text-reset ml-3 my-1 p-1" href="#%s">%s</a>', sprintf('sp-%s-%s', h($group), h($section)), h($section));
        }
        $nav .= '</nav>';
    }
    $nav .=     '</nav>';
    $nav .= '</nav>';
    return $nav;
}

function isLeaf($setting)
{
    return !empty($setting['name']) && !empty($setting['type']);
}

?>


<script>
    const variantFromSeverity = <?= json_encode($variantFromSeverity) ?>;
    const settingsFlattened = <?= json_encode($settingsFlattened) ?>;
    let selectData = []
    for (const settingName in settingsFlattened) {
        if (Object.hasOwnProperty.call(settingsFlattened, settingName)) {
            const setting = settingsFlattened[settingName];
            const selectID = settingName.replaceAll('.', '_')
            selectData.push({
                id: selectID,
                text: setting.name,
                setting: setting
            })
        }
    }
    $(document).ready(function() {
        $('[data-spy="scroll"]').on('activate.bs.scrollspy', function(evt, {relatedTarget}) {
            const $associatedLink = $(`#navbar-scrollspy-setting nav.nav-pills .nav-link[href="${relatedTarget}"]`)
            let $associatedNav
            if ($associatedLink.hasClass('main-group')) {
                $associatedNav = $associatedLink.next()
            } else {
                $associatedNav = $associatedLink.parent()
            }
            const $allNavs = $('#navbar-scrollspy-setting nav.nav-pills.sub-group')
            $allNavs.removeClass('group-active').hide()
            $associatedNav.addClass('group-active').show()
        })

        $('.settings-tabs a[data-toggle="tab"]').on('shown.bs.tab', function (event) {
            $('[data-spy="scroll"]').trigger('scroll.bs.scrollspy')
        })

        $("#search-settings").select2({
            data: selectData,
            placeholder: '<?= __('Search setting by typing here...') ?>',
            templateResult: formatSettingSearchResult,
            templateSelection: formatSettingSearchSelection,
            matcher: settingMatcher,
            sorter: settingSorter,
        })
            .on('select2:select', function (e) {
                const selected = e.params.data
                const settingPath = selected.setting['setting-path']
                const settingPathTokenized = settingPath.split('.')
                const tabName = settingPathTokenized[0]
                const IDtoFocus = 'sp-' + settingPathTokenized.slice(1).join('-')
                const $navController = $('.settings-tabs').find('a.nav-link').filter(function() {
                    return $(this).text() == tabName
                })
                if ($navController.length == 1) {
                    $toFocus = $(`#${IDtoFocus}`).parent()
                    if ($navController.hasClass('active')) {
                        $toFocus[0].scrollIntoView()
                        $toFocus.find(`input#${selected.id}`).focus()
                    } else {
                        $navController.on('shown.bs.tab.after-selection', () => {
                            $toFocus[0].scrollIntoView()
                            $toFocus.find(`input#${selected.id}`).focus()
                            $navController.off('shown.bs.tab.after-selection')
                        }).tab('show')
                    }
                }
                $("#search-settings").val(null).trigger('change.select2');
            })
        
        $('.tab-content input').on('input', function() {
            if ($(this).attr('type') == 'checkbox') {
                const $input = $(this)
                const $inputGroup = $(this).closest('.form-group')
                const settingName = $(this).data('setting-name')
                const settingValue = $(this).is(':checked')
                saveSetting($inputGroup[0], $input, settingName, settingValue)
            } else {
                handleSettingValueChange($(this))
            }
        })

        $('.tab-content .input-group-actions .btn-save-setting').click(function() {
            const $input = $(this).closest('.input-group').find('input')
            const settingName = $input.data('setting-name')
            const settingValue = $input.val()
            saveSetting(this, $input, settingName, settingValue)
        })
        $('.tab-content .input-group-actions .btn-reset-setting').click(function() {
            const $btn = $(this)
            const $input = $btn.closest('.input-group').find('input')
            const oldValue = settingsFlattened[$input.data('setting-name')].value
            $input.val(oldValue)
            handleSettingValueChange($input)
        })
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
        })
    }

    function settingMatcher(params, data) {
        if (params.term == null || params.term.trim() === '') {
            return data;
        }
        if (data.text === undefined || data.setting === undefined) {
            return null;
        }
        let modifiedData = $.extend({}, data, true);
        const loweredTerms = params.term.trim().toLowerCase().split(' ')
        for (let i = 0; i < loweredTerms.length; i++) {
            const loweredTerm = loweredTerms[i];
            const settingNameMatch = data.setting['true-name'].toLowerCase().indexOf(loweredTerm) > -1 || data.text.toLowerCase().indexOf(loweredTerm) > -1
            const settingGroupMatch = data.setting['setting-path'].toLowerCase().indexOf(loweredTerm) > -1
            const settingDescMatch = data.setting.description.toLowerCase().indexOf(loweredTerm) > -1
            if (settingNameMatch || settingGroupMatch || settingDescMatch) {
                modifiedData.matchPriority = (settingNameMatch ? 10 : 0) + (settingGroupMatch ? 5 : 0) + (settingDescMatch ? 1 : 0)
            }
        }
        if (modifiedData.matchPriority > 0) {
            return modifiedData;
        }
        return null;
    }

    function settingSorter(data) {
        let sortedData = data.slice(0)
        sortedData = sortedData.sort((a, b) => {
            return a.matchPriority == b.matchPriority ? 0 : (b.matchPriority - a.matchPriority)
        })
        return sortedData;
    }

    function formatSettingSearchResult(state) {
        if (!state.id) {
            return state.text;
        }
        const $state = $('<div/>').append(
            $('<div/>').addClass('d-flex justify-content-between')
                .append(
                    $('<span/>').addClass('font-weight-bold').text(state.text),
                    $('<span/>').addClass('font-weight-light').text(state.setting['setting-path'].replaceAll('.', ' ▸ '))
                ),
            $('<div/>').addClass('font-italic font-weight-light ml-3').text(state.setting['description'])
        )
        return $state
    }
    
    function formatSettingSearchSelection(state) {
        return state.text
    }

    function handleSettingValueChange($input) {
        const oldValue = settingsFlattened[$input.data('setting-name')].value
        const newValue = ($input.attr('type') == 'checkbox' ? $input.is(':checked') : $input.val())
        if (newValue == oldValue) {
            restoreWarnings($input)
        } else {
            removeWarnings($input)
        }
    }

    function removeWarnings($input) {
        const $inputGroup = $input.closest('.input-group')
        const $inputGroupAppend = $inputGroup.find('.input-group-append')
        const $saveButton = $inputGroup.find('button.btn-save-setting')
        $input.removeClass(['is-invalid', 'border-warning', 'border-danger'])
        $inputGroupAppend.removeClass('d-none')
        $inputGroup.parent().find('.invalid-feedback').removeClass('d-block')
    }

    function restoreWarnings($input) {
        const $inputGroup = $input.closest('.input-group')
        const $inputGroupAppend = $inputGroup.find('.input-group-append')
        const $saveButton = $inputGroup.find('button.btn-save-setting')
        const setting = settingsFlattened[$input.data('setting-name')]
        if (setting.error) {
            borderVariant = setting.severity !== undefined ? variantFromSeverity[setting.severity] : 'warning'
            $input.addClass(['is-invalid', `border-${borderVariant}`])
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
        const $settings = $callout.find('input')
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

</script>

<style>
    #navbar-scrollspy-setting nav.nav-pills .nav-link {
        background-color: unset !important;
        color: black;
        display: block;
    }

    #navbar-scrollspy-setting nav.nav-pills .nav-link:not(.main-group).active {
        color: #007bff !important;
        font-weight: bold;
    }

    #navbar-scrollspy-setting nav.nav-pills .nav-link.main-group:before {
        margin-right: 0.25em;
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        -webkit-font-smoothing: antialiased;
        display: inline-block;
        font-style: normal;
        font-variant: normal;
        text-rendering: auto;
        line-height: 1;
    }

    #navbar-scrollspy-setting nav.nav-pills .nav-link.main-group.active:before {
        content: "\f0d7";
    }

    #navbar-scrollspy-setting nav.nav-pills .nav-link.main-group:before {
        content: "\f0da";
    }

    .select2-container {
        max-width: 100%;
        min-width: 100%;
    }
</style>