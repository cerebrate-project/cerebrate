<?php
use Cake\ORM\TableRegistry;

function isLeaf($setting)
{
    return !empty($setting['name']) && !empty($setting['type']);
}

function getResolvableID($sectionName, $panelName = false)
{
    $id = sprintf('sp-%s', preg_replace('/(\.|\W)/', '_', h($sectionName)));
    if (!empty($panelName)) {
        $id .= '-' . preg_replace('/(\.|\W)/', '_', h($panelName));
    }
    return $id;
}


$settings = [
    __('Appearance') => [
        'ui.bsTheme' => [
            'description' => 'The Bootstrap theme to use for the application',
            'default' => 'default',
            'name' => 'UI Theme',
            'options' => (function () {
                $instanceTable = TableRegistry::getTableLocator()->get('Instance');
                $themes = $instanceTable->getAvailableThemes();
                return array_combine($themes, $themes);
            })(),
            'severity' => 'info',
            'type' => 'select'
        ],
    ],
    __('Bookmarks') => 'Bookmarks',
    __('Account Security') => 'Account Security',
];

$cardNavs = array_keys($settings);
$cardContent = [];

$sectionHtml = '';
foreach ($settings[__('Appearance')] as $sectionName => $sectionContent) {
    $sectionHtml .= $this->element('Settings/panel', [
        'sectionName' => $sectionName,
        'panelName' => $sectionName,
        'panelSettings' => $sectionContent,
    ]);
}
$cardContent[] = $sectionHtml;
$cardContent[] = $settings[__('Bookmarks')];
$cardContent[] = $settings[__('Account Security')];

$tabsOptions = [
    'vertical' => true,
    'vertical-size' => 2,
    'card' => true,
    'pills' => true,
    'justify' => 'center',
    'nav-class' => ['settings-tabs'],
    'data' => [
        'navs' => $cardNavs,
        'content' => $cardContent
    ]
];
$tabs = $this->Bootstrap->tabs($tabsOptions);
?>

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