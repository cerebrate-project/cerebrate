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
    $panelID = getResolvableID($sectionName, $panelName);
    $panelHTML .= sprintf('<h4 id="%s"><a class="text-reset text-decoration-none" href="#%s">%s%s</a></h4>',
        $panelID,
        $panelID,
        !empty($panelSettings['_icon']) ? $this->Bootstrap->icon($panelSettings['_icon'], ['class' => 'mr-1']) : '',
        h($panelName)
    );
    if (!empty($panelSettings['_description'])) {
        $panelHTML .= $this->Bootstrap->genNode('div', [
            'class' => ['mb-1',],
        ], h($panelSettings['_description']));
    }
    $groupIssueSeverity = false;
    foreach ($panelSettings as $singleSettingName => $singleSetting) {
        if (substr($singleSettingName, 0, 1) == '_') {
            continue;
        }
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