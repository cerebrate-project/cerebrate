<?php
    echo $this->element('genericElements/Form/genericForm', [
        'data' => [
            'description' => __('Roles define global rules for a set of users, including first and foremost access controls to certain functionalities.'),
            'model' => 'Roles',
            'fields' => [
                [
                    'field' => 'individual_id',
                    'type' => 'dropdown',
                    'label' => __('Associated individual'),
                    'options' => $dropdownData['individual']
                ],
                [
                    'field' => 'role_id',
                    'type' => 'dropdown',
                    'label' => __('Role'),
                    'options' => $dropdownData['role']
                ],
                [
                    'field' => 'password',
                    'label' => __('Password')
                ],
                [
                    'field' => 'confirm_password',
                    'label' => __('Confirm Password')
                ],
                [
                    'field' => 'disabled',
                    'type' => 'checkbox',
                    'label' => 'Disable'
                ]
            ],
            'submit' => [
                'action' => $this->request->getParam('action')
            ]
        ]
    ]);
?>
</div>
