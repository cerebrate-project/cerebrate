<?php
echo $this->element('genericElements/Form/genericForm', [
    'data' => [
        'description' => __('User settings are used to register setting tied to user profile.'),
        'model' => 'UserSettings',
        'fields' => [
            sprintf(
                '<div class="row mb-3"><div class="col-sm-2 form-label">%s</div><div class="col-sm-10 font-monospace">%s</div>',
                __('Setting Name'),
                h($settingName)
            ),
            [
                'field' => 'value',
                'label' => __('Setting Value'),
                'type' => 'codemirror',
                'codemirror' => [
                    'height' => '10rem',
                    'mode' => [
                        'name' => 'text',
                    ],
                ]
            ],
        ],
        'submit' => [
            'action' => $this->request->getParam('action')
        ]
    ]
]);
?>
</div>