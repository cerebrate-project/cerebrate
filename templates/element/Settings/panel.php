<?php
$panelHTML = '';
if (isLeaf($panelSettings)) {
    $singleSetting = $this->element('Settings/fieldGroup', [
        'panelName' => $panelName,
        'panelSettings' => $panelSettings,
        'settingName' => $panelName,
        'setting' => $panelSettings,
    ]);
    $panelHTML = "<div>{$singleSetting}</div>";
} else {
    $panelID = sprintf('sp-%s-%s', h($sectionName), h($panelName)); 
    $panelHTML .= sprintf('<h4 id="%s"><a class="text-reset text-decoration-none" href="#%s">%s</a></h4>', $panelID, $panelID, h($panelName));
    $groupIssueSeverity = false;
    foreach ($panelSettings as $singleSettingName => $singleSetting) {
        $singleSettingHTML = $this->element('Settings/fieldGroup', [
            'panelName' => $panelName,
            'panelSettings' => $panelSettings,
            'settingName' => $singleSettingName,
            'setting' => $singleSetting,
        ]);
        $panelHTML .= sprintf('<div class="ml-3">%s</div>', $singleSettingHTML);
        if (!empty($singleSetting['error'])) {
            $settingVariant = $this->get('variantFromSeverity')[$singleSetting['severity']];
            if ($groupIssueSeverity != 'danger') {
                if ($groupIssueSeverity != 'warning') {
                    $groupIssueSeverity = $settingVariant;
                }
            }
        }
    }
    $panelHTML = $this->Bootstrap->genNode('div', [
        'class' => [
            'shadow',
            'p-2',
            'mb-4',
            'rounded',
            'settings-group',
            (!empty($groupIssueSeverity) ? "callout callout-${groupIssueSeverity}" : ''),
            ($this->get('darkMode') ? 'bg-dark' : 'bg-light')
        ],
    ], $panelHTML);
}
echo $panelHTML;