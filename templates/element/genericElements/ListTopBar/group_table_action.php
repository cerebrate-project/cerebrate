<?php

use App\Utility\UI\IndexSetting;

if (empty($data['table_setting_id']) && empty($model)) {
    throw new Exception(__('`table_setting_id` must be set in order to use the `table_action` table topbar'));
}
$data['table_setting_id'] = !empty($data['table_setting_id']) ? $data['table_setting_id'] : IndexSetting::getIDFromTable($model);
$tableSettings = IndexSetting::getTableSetting($loggedUser, $data['table_setting_id']);
$compactDisplay = !empty($tableSettings['compact_display']);

$availableColumnsHtml = $this->element('/genericElements/ListTopBar/group_table_action/hiddenColumns', [
    'table_data' => $table_data,
    'tableSettings' => $tableSettings,
    'table_setting_id' => $data['table_setting_id'],
]);

$metaTemplateColumnMenu = [];
if (!empty($meta_templates)) {
    foreach ($meta_templates as $meta_template) {
        $numberActiveMetaField = !empty($tableSettings['visible_meta_column'][$meta_template->id]) ? count($tableSettings['visible_meta_column'][$meta_template->id]) : 0;
        $metaTemplateColumnMenu[] = [
            'text' => $meta_template->name,
            'badge' => [
                'text' => $numberActiveMetaField,
                'variant' => 'secondary',
                'title' => __n('{0} meta-field active for this meta-template', '{0} meta-fields active for this meta-template', $numberActiveMetaField, $numberActiveMetaField),
            ],
            'keepOpen' => true,
            'menu' => [
                [
                    'html' => $this->element('/genericElements/ListTopBar/group_table_action/hiddenMetaColumns', [
                        'tableSettings' => $tableSettings,
                        'table_setting_id' => $data['table_setting_id'],
                        'meta_template' => $meta_template,
                    ])
                ]
            ],
        ];
    }
}
$indexColumnMenu = array_merge(
    [['header' => true, 'text' => sprintf('%s\'s fields', $this->request->getParam('controller'))]],
    [['html' => $availableColumnsHtml]],
    [['header' => true, 'text' => __('Meta Templates'), 'icon' => 'object-group',]],
    $metaTemplateColumnMenu
);

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
            [
                'text' => __('Show/hide columns'),
                'icon' => 'eye-slash',
                'keepOpen' => true,
                'menu' => $indexColumnMenu,
            ],
            [
                'html' => $compactDisplayHtml,
            ]
        ]
    ]);
    ?>
<?php endif; ?>
