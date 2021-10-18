<?php

$variantFromSeverity = [
    'critical' => 'danger',
    'warning' => 'warning',
    'info' => 'info',
];

$navLinks = [];
$tabContents = [];

foreach ($settingsProvider as $settingTitle => $settingContent) {
    $navLinks[] = h($settingTitle);
    $tabContents[] = $this->element('Settings/category', [
        'settings' => $settingContent,
        'includeScrollspy' => true,
    ]);
}

array_unshift($navLinks, __('Settings Diagnostic'));
$notice = $this->element('Settings/notice', [
    'variantFromSeverity' => $variantFromSeverity,
    'notices' => $notices,
]);
array_unshift($tabContents, $notice);
?>

<script>
    window.variantFromSeverity = <?= json_encode($variantFromSeverity) ?>;
    window.settingsFlattened = <?= json_encode($settingsFlattened) ?>;
    window.saveSettingURL = '/instance/saveSetting'
</script>

<div class="px-5">
    <div class="mb-3 mt-2">
        <?=
        $this->element('Settings/search', [
            'settingsFlattened' => $settingsFlattened,
        ]);
        ?>
    </div>
    <?php
    $tabsOptions = [
        'card' => false,
        'pills' => false,
        'justify' => 'center',
        'nav-class' => ['settings-tabs'],
        'data' => [
            'navs' => $navLinks,
            'content' => $tabContents
        ]
    ];
    echo $this->Bootstrap->tabs($tabsOptions);
    echo $this->Html->script('settings');
    ?>
</div>

<style>
    .input-group-actions {
        z-index: 5;
    }

    .form-control[type="number"]~div>a.btn-reset-setting {
        left: -3em;
    }

    select.custom-select[multiple][data-setting-name]~span.select2-container {
        min-width: unset;
    }

    span.select2-container--open {
        min-width: unset;
    }
</style>