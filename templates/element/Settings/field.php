<?php
    if ($setting['type'] == 'string' || $setting['type'] == 'textarea' || empty($setting['type'])) {
        $input = (function ($settingName, $setting, $appView) {
            $settingId = str_replace('.', '_', $settingName);
            return $appView->Bootstrap->genNode(
                $setting['type'] == 'textarea' ? 'textarea' : 'input',
                [
                    'class' => [
                        'form-control',
                        'pe-4',
                        (!empty($setting['error']) ? 'is-invalid' : ''),
                        (!empty($setting['error']) ? "border-{$appView->get('variantFromSeverity')[$setting['severity']]}" : ''),
                        (!empty($setting['error']) ? $appView->get('variantFromSeverity')[$setting['severity']] : ''),
                    ],
                    ($setting['type'] == 'textarea' ? '' : 'type') => ($setting['type'] == 'textarea' ? '' : 'text'),
                    'id' => $settingId,
                    'data-setting-name' => $settingName,
                    'value' => isset($setting['value']) ? $setting['value'] : "",
                    'placeholder' => $setting['default'] ?? '',
                    'aria-describedby' => "{$settingId}Help"
                ]
            );
        })($settingName, $setting, $this);

    } elseif ($setting['type'] == 'boolean') {
        $input = (function ($settingName, $setting, $appView) {
            $settingId = str_replace('.', '_', $settingName);
            $switch = $appView->Bootstrap->genNode('input', [
                'class' => [
                    'custom-control-input',
                    (!empty($setting['error']) ? 'is-invalid' : ''),
                    (!empty($setting['error']) ? $appView->get('variantFromSeverity')[$setting['severity']] : ''),
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
                    'form-switch',
                ],
            ], implode('', [$switch, $label]));
            return $container;
        })($settingName, $setting, $this);
        $description = '';

    } elseif ($setting['type'] == 'integer') {
        $input = (function ($settingName, $setting, $appView) {
            $settingId = str_replace('.', '_', $settingName);
            return $appView->Bootstrap->genNode('input', [
                'class' => [
                    'form-control',
                    (!empty($setting['error']) ? 'is-invalid' : ''),
                    (!empty($setting['error']) ? "border-{$appView->get('variantFromSeverity')[$setting['severity']]}" : ''),
                    (!empty($setting['error']) ? $appView->get('variantFromSeverity')[$setting['severity']] : ''),
                ],
                'type' => 'number',
                'min' => '0',
                'step' => 1,
                'id' => $settingId,
                'data-setting-name' => $settingName,
                'aria-describedby' => "{$settingId}Help"
            ]);
        })($settingName, $setting, $this);

    } elseif ($setting['type'] == 'select' || $setting['type'] == 'multi-select') {
        $input = (function ($settingName, $setting, $appView) {
            $settingId = str_replace('.', '_', $settingName);
            $setting['value'] = $setting['value'] ?? '';
            $options = [];
            if ($setting['type'] == 'select') {
                $options[] = $appView->Bootstrap->genNode('option', ['value' => '-1', 'data-is-empty-option' => '1'], __('Select an option'));
            }
            foreach ($setting['options'] as $key => $value) {
                $options[] = $appView->Bootstrap->genNode('option', [
                    'class' => [],
                    'value' => $key,
                    ($setting['value'] == $key ? 'selected' : '') => $setting['value'] == $value ? 'selected' : '',
                ], h($value));
            }
            $options = implode('', $options);
            return $appView->Bootstrap->genNode('select', [
                'class' => [
                    'form-select',
                    'pe-4',
                    (!empty($setting['error']) ? 'is-invalid' : ''),
                    (!empty($setting['error']) ? "border-{$appView->get('variantFromSeverity')[$setting['severity']]}" : ''),
                    (!empty($setting['error']) ? $appView->get('variantFromSeverity')[$setting['severity']] : ''),
                ],
                ($setting['type'] == 'multi-select' ? 'multiple' : '') => '',
                'id' => $settingId,
                'data-setting-name' => $settingName,
                'placeholder' => $setting['default'] ?? '',
                'aria-describedby' => "{$settingId}Help"
            ], $options);
        })($settingName, $setting, $this);
    }
    echo $input;
