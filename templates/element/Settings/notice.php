<?php
$mainNoticeHeading = [
    'critical' => __('Your Cerebrate instance requires immediate attention.'),
    'warning' => __('Issues found, it is recommended that you resolve them.'),
    'info' => __('There are some optional settings that are incorrect or not set.'),
];
$headingPerLevel = [
    'critical' => __('Critical settings'),
    'warning' => __('Warning settings'),
    'info' => __('Info settings'),
];
$noticeDescriptionPerLevel = [
    'critical' => __('Cerebrate will not operate correctly or will be unsecure until these issues are resolved.'),
    'warning' => __('Some of the features of Cerebrate cannot be utilised until these issues are resolved.'),
    'info' => __('There are some optional tweaks that could be done to improve the looks of your Cerebrate instance.'),
];

$alertVariant = 'info';
$skipHeading = false;
$alertBody = '';
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

$alertBody = $this->Bootstrap->table([
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
echo $settingNotice;