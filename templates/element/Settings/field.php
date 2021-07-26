<?php
    if ($setting['type'] == 'string' || empty($setting['type'])) {
        $input = (function ($settingName, $setting, $appView) {
            $settingId = str_replace('.', '_', $settingName);
            return $appView->Bootstrap->genNode('input', [
                'class' => [
                    'form-control',
                    'pr-4',
                    (!empty($setting['error']) ? 'is-invalid' : ''),
                    (!empty($setting['error']) ? "border-{$appView->get('variantFromSeverity')[$setting['severity']]}" : ''),
                    (!empty($setting['error']) ? $appView->get('variantFromSeverity')[$setting['severity']] : ''),
                ],
                'type' => 'text',
                'id' => $settingId,
                'data-setting-name' => $settingName,
                'value' => isset($setting['value']) ? $setting['value'] : "",
                'placeholder' => $setting['default'] ?? '',
                'aria-describedby' => "{$settingId}Help"
            ]);
        })($settingName, $setting, $this);

    } elseif ($setting['type'] == 'boolean') {
        $input = (function ($settingName, $setting, $appView) {
            $settingId = str_replace('.', '_', $settingName);
            $switch = $appView->Bootstrap->genNode('input', [
                'class' => [
                    'custom-control-input',
                    (!empty($setting['error']) ? 'is-invalid' : ''),
                    (!empty($setting['error']) && $setting['severity'] == 'warning' ? 'warning' : ''),
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
                    'custom-switch',
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

    } elseif ($setting['type'] == 'select') {
        $input = (function ($settingName, $setting, $appView) {
            $settingId = str_replace('.', '_', $settingName);
            $setting['value'] = $setting['value'] ?? '';
            $options = [
                $appView->Bootstrap->genNode('option', ['value' => '-1', 'data-is-empty-option' => '1'], __('Select an option'))
            ];
            foreach ($setting['options'] as $key => $value) {
                $options[] = $appView->Bootstrap->genNode('option', [
                    'class' => [],
                    'value' => $key,
                    ($setting['value'] == $value ? 'selected' : '') => $setting['value'] == $value ? 'selected' : '',
                ], h($value));
            }
            $options = implode('', $options);
            return $appView->Bootstrap->genNode('select', [
                'class' => [
                    'custom-select',
                    'pr-4',
                    (!empty($setting['error']) ? 'is-invalid' : ''),
                    (!empty($setting['error']) ? "border-{$appView->get('variantFromSeverity')[$setting['severity']]}" : ''),
                    (!empty($setting['error']) ? $appView->get('variantFromSeverity')[$setting['severity']] : ''),
                ],
                'type' => 'text',
                'id' => $settingId,
                'data-setting-name' => $settingName,
                'placeholder' => $setting['default'] ?? '',
                'aria-describedby' => "{$settingId}Help"
            ], $options);
        })($settingName, $setting, $this);

    } elseif ($setting['type'] == 'multi-select') {
        $input = (function ($settingName, $setting, $appView) {
            return '';
        })($settingName, $setting, $this);
    }
    echo $input;
