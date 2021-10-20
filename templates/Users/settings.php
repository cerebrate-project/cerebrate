<?php
$navLinks = [];
$tabContents = [];

foreach ($settingsProvider as $settingTitle => $settingContent) {
    $navLinks[] = h($settingTitle);
    $tabContents[] = $this->element('Settings/category', [
        'settings' => $settingContent,
        'includeScrollspy' => false,
    ]);
}

$navLinks[] = __('Bookmarks');
$tabContents[] = $this->element('UserSettings/saved-bookmarks', [
    'bookmarks' => !empty($user->user_settings_by_name['ui.bookmarks']['value']) ? json_decode($user->user_settings_by_name['ui.bookmarks']['value'], true) : []
]);

$tabsOptions = [
    'vertical' => true,
    'vertical-size' => 2,
    'card' => true,
    'pills' => true,
    'justify' => 'center',
    'nav-class' => ['settings-tabs'],
    'data' => [
        'navs' => $navLinks,
        'content' => $tabContents
    ]
];
$tabs = $this->Bootstrap->tabs($tabsOptions);
echo $this->Html->script('settings');
?>

<script>
    window.settingsFlattened = <?= json_encode($settingsFlattened) ?>;
    window.saveSettingURL = '/userSettings/saveSetting'
</script>

<h2 class="fw-light"><?= __('Account settings') ?></h2>
<div class="p-2">
    <div>
        <div>
            <span class="fw-bold font-monospace me-2 fs-5"><?= h($user->username) ?></span>
            <span><?= h($user->individual->full_name) ?></span>
        </div>
        <div class="fw-light"><?= __('Your personnal account') ?></div>
    </div>
    <div class="mt-2">
        <?= $tabs ?>
    </div>
</div>