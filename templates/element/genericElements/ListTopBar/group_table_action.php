<?php
if (empty($data['table_setting_id'])) {
    throw new Exception(__('`table_setting_id` must be set in order to use the `table_action` table topbar'));
}
$tableSettings = !empty($loggedUser->user_settings_by_name['ui.table_setting']['value']) ? json_decode($loggedUser->user_settings_by_name['ui.table_setting']['value'], true) : [];
$tableSettings = !empty($tableSettings[$data['table_setting_id']]) ? $tableSettings[$data['table_setting_id']] : [];
$compactDisplay = !empty($tableSettings['compact_display']);

$availableColumnsHtml = $this->element('/genericElements/ListTopBar/group_table_action/hiddenColumns', [
    'table_data' => $table_data,
    'tableSettings' => $tableSettings,
    'table_setting_id' => $data['table_setting_id'],
]);
$compactDisplayHtml = $this->element('/genericElements/ListTopBar/group_table_action/compactDisplay', [
    'table_data' => $table_data,
    'tableSettings' => $tableSettings,
    'table_setting_id' => $data['table_setting_id'],
    'compactDisplay' => $compactDisplay
]);
?>
<?php if (!isset($data['requirement']) || $data['requirement']) : ?>
    <?php
    echo $this->Bootstrap->dropdownMenu([
        'dropdown-class' => 'ms-1',
        'alignment' => 'end',
        'direction' => 'down',
        'toggle-button' => [
            'icon' => 'sliders-h',
            'variant' => 'primary',
        ],
        'submenu_alignment' => 'end',
        'submenu_direction' => 'start',
        'params' => [
            'data-table-random-value' => $tableRandomValue,
            'data-table_setting_id' => $data['table_setting_id'],
        ],
        'menu' => [
            // [
            //     'text' => __('Group by'),
            //     'icon' => 'layer-group',
            //     'menu' => [
            //         [
            //             'text' => 'fields to be grouped by', TODO:implement
            //         ]
            //     ],
            // ],
            [
                'text' => __('Show/hide columns'),
                'icon' => 'eye-slash',
                'keepOpen' => true,
                'menu' => [
                    [
                        'html' => $availableColumnsHtml,
                    ]
                ],
            ],
            [
                'html' => $compactDisplayHtml,
            ]
        ]
    ]);
    ?>
<?php endif; ?>
