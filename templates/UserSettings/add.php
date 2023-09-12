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
                    'value' => !is_null($user_id) ? $user_id : '',
                    'disabled' => !empty($is_edit),
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
