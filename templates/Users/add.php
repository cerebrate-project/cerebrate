<?php
    use Cake\Core\Configure;
    $passwordRequired = false;
    $showPasswordField = false;
    if ($this->request->getParam('action') === 'add') {
        $dropdownData['individual'] = ['new' => __('New individual')] + $dropdownData['individual'];
        if (!Configure::check('password_auth.enabled') || Configure::read('password_auth.enabled')) {
            $passwordRequired = 'required';
        }
    }
    if (!Configure::check('password_auth.enabled') || Configure::read('password_auth.enabled')) {
        $showPasswordField = true;
    }
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
                    'field' => 'individual.email',
                    'stateDependence' => [
                        'source' => '#individual_id-field',
                        'option' => 'new'
                    ],
                    'required' => false
                ],
                [
                    'field' => 'individual.first_name',
                    'label' => 'First name',
                    'stateDependence' => [
                        'source' => '#individual_id-field',
                        'option' => 'new'
                    ],
                    'required' => false
                ],
                [
                    'field' => 'individual.last_name',
                    'label' => 'Last name',
                    'stateDependence' => [
                        'source' => '#individual_id-field',
                        'option' => 'new'
                    ],
                    'required' => false
                ],
                [
                    'field' => 'username',
                    'autocomplete' => 'off'
                ],
                [
                    'field' => 'organisation_id',
                    'type' => 'dropdown',
                    'label' => __('Associated organisation'),
                    'options' => $dropdownData['organisation'],
                    'default' => $loggedUser['organisation_id']
                ],
                [
                    'field' => 'password',
                    'label' => __('Password'),
                    'type' => 'password',
                    'required' => $passwordRequired,
                    'autocomplete' => 'new-password',
                    'value' => '',
                    'requirements' => $showPasswordField,
                ],
                [
                    'field' => 'confirm_password',
                    'label' => __('Confirm Password'),
                    'type' => 'password',
                    'required' => $passwordRequired,
                    'autocomplete' => 'off',
                    'requirements' => $showPasswordField,
                ],
                [
                    'field' => 'role_id',
                    'type' => 'dropdown',
                    'label' => __('Role'),
                    'options' => $dropdownData['role'],
                    'default' => $defaultRole ?? null
                ],
                [
                    'field' => 'disabled',
                    'type' => 'checkbox',
                    'label' => 'Disable'
                ],
            ],
            'submit' => [
                'action' => $this->request->getParam('action')
            ]
        ]
    ]);
?>
</div>
