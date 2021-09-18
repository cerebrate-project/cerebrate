<?php
    $settingId = str_replace('.', '_', $settingName);

    $dependsOnHtml = '';
    if (!empty($setting['dependsOn'])) {
        $dependsOnHtml = $this->Bootstrap->genNode('span', [
            'class' => [
                'ms-1',
                'd-inline-block',
                'depends-on-icon'
            ],
            'style' => 'min-width: 0.75em;',
            'title' => __('This setting depends on the validity of: {0}', h($setting['dependsOn'])),
        ], $this->Bootstrap->genNode('sup', [
            'class' => $this->FontAwesome->getClass('info'),
        ]));
    }
    $label = $this->Bootstrap->genNode('label', [
        'class' => ['form-label', 'fw-bolder', 'mb-0'],
        'for' => $settingId
    ], sprintf('<a id="lb-%s" href="#lb-%s" class="text-reset text-decoration-none">%s</a>', h($settingId), h($settingId), h($setting['name'])) . $dependsOnHtml);

    $description = '';
    if (!empty($setting['description']) && (empty($setting['type']) || $setting['type'] != 'boolean')) {
        $description = $this->Bootstrap->genNode('small', [
            'class' => ['form-text', 'text-muted', 'mt-0'],
            'id' => "{$settingId}Help"
        ], h($setting['description']));
    }
    $textColor = 'text-warning';
    if (!empty($setting['severity'])) {
        $textColor = "text-{$this->get('variantFromSeverity')[$setting['severity']]}";
    }
    $validationError = $this->Bootstrap->genNode('div', [
        'class' => ['d-block', 'invalid-feedback', $textColor],
    ], (!empty($setting['error']) ? h($setting['errorMessage']) : ''));

    $input = $this->element('Settings/field', [
        'setting' => $setting,
        'settingName' => $settingName,
    ]);

    $inputGroupSave = $this->Bootstrap->genNode('div', [
        'class' => ['d-none', 'position-relative', 'input-group-actions'],
    ], implode('', [
            $this->Bootstrap->genNode('a', [
                'class' => ['position-absolute', 'fas fa-times', 'p-abs-center-y', 'text-reset text-decoration-none', 'btn-reset-setting'],
                'href' => '#',
            ]),
            $this->Bootstrap->genNode('button', [
                'class' => ['btn', 'btn-success', 'btn-save-setting'],
                'type' => 'button',
            ], __('save')),
    ]));
    $inputGroup = $this->Bootstrap->genNode('div', [
        'class' => ['input-group'],
    ], implode('', [$input, $inputGroupSave]));

    $container = $this->Bootstrap->genNode('div', [
        'class' => ['setting-group', 'row', 'mb-2']
    ], implode('', [$label, $inputGroup, $description, $validationError]));
    
    echo $container;
?>