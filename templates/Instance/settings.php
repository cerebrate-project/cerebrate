<?php
// debug($settings);
// debug($settingsProvider);
$settingTable = genLevel0($settingsProvider, $this);
?>
<div class="px-5">
    <div class="mb-3">
        <input class="form-control" type="text" id="search" placeholder="<?= __('Search settings') ?>" aria-describedby="<?= __('Search setting input') ?>">
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
    $tabsOptions0 = [
        // 'vertical' => true,
        // 'vertical-size' => 2,
        'card' => false,
        'pills' => false,
        'justify' => 'center',
        'content-class' => ['mt-2'],
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
                return !isLeaf($level2Setting[$settingGroupName]);
            }
        );
    }
    $contentHtml = implode('', $content1);
    $scrollspyNav = genScrollspyNav($nav1);
    $mainPanelHeight = 'calc(100vh - 8px - 42px - 1rem - 56px - 38px - 1rem)';
    $container =  '<div class="d-flex">';
    $container .=   "<div class=\"\" style=\"flex: 0 0 10em;\">{$scrollspyNav}</div>";
    $container .=   "<div data-spy=\"scroll\" data-target=\"#navbar-scrollspy-setting\" data-offset=\"24\" style=\"height: {$mainPanelHeight}\" class=\"p-3 overflow-auto position-relative flex-grow-1\">{$contentHtml}</div>";
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
        foreach ($setting as $singleSettingName => $singleSetting) {
            $tmp = genSingleSetting($singleSettingName, $singleSetting, $appView);
            $settingGroup .= sprintf('<div class="ml-3">%s</div>', $tmp);
        }
        $settingGroup = $appView->Bootstrap->genNode('div', [
            'class' => ['shadow', 'p-2', 'mb-4', 'rounded', ($appView->get('darkMode') ? 'bg-dark' : 'bg-light')],
        ], $settingGroup);
    }
    return $settingGroup;
}

function genSingleSetting($settingName, $setting, $appView)
{
    $label = $appView->Bootstrap->genNode('label', [
        'class' => ['font-weight-bolder', 'mb-0'],
        'for' => $settingName
    ], h($setting['name']));
    $description = '';
    if (!empty($setting['description'])) {
        $description = $appView->Bootstrap->genNode('small', [
            'class' => ['form-text', 'text-muted', 'mt-0'],
            'id' => "{$settingName}Help"
        ], h($setting['description']));
    }
    $inputGroup = '';
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
    $container = $appView->Bootstrap->genNode('div', [
        'class' => ['form-group', 'mb-2']
    ], implode('', [$label, $input, $description]));
    return $container;
}

function genInputString($settingName, $setting, $appView)
{
    return $appView->Bootstrap->genNode('input', [
        'class' => [
            'form-control'
        ],
        'type' => 'text',
        'id' => $settingName,
        'value' => isset($setting['value']) ? $setting['value'] : "",
        'aria-describedby' => "{$settingName}Help"
    ]);
}
function genInputCheckbox($settingName, $setting, $appView)
{
    $switch = $appView->Bootstrap->genNode('input', [
        'class' => [
            'custom-control-input'
        ],
        'type' => 'checkbox',
        'value' => !empty($setting['value']) ? 1 : 0,
        'checked' => !empty($setting['value']) ? 'checked' : '',
        'id' => $settingName,
    ]);
    $label = $appView->Bootstrap->genNode('label', [
        'class' => [
            'custom-control-label'
        ],
        'for' => $settingName,
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
    return $appView->Bootstrap->genNode('input', [
        'class' => [
            'form-control'
        ],
        'params' => [
            'type' => 'integer',
            'id' => $settingName,
            'aria-describedby' => "{$settingName}Help"
        ]
    ]);
}
function genInputSelect($settingName, $setting, $appView)
{
}
function genInputMultiSelect($settingName, $setting, $appView)
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
    })

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
</style>