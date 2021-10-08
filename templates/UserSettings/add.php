<?php
    echo $this->element('genericElements/Form/genericForm', [
        'data' => [
            'description' => __('User settings are used to register setting tied to user profile.'),
            'model' => 'UserSettings',
            'fields' => [
                [
                    'field' => 'user_id',
                    'type' => 'dropdown',
                    'label' => __('User'),
                    'options' => $dropdownData['user'],
                    'value' => !empty($user_id) ? $user_id : '',
                    'disabled' => !empty($user_id),
                ],
                [
                    'field' => 'name',
                    'label' => __('Setting Name'),
                ],
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
