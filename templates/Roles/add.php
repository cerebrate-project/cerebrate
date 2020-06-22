<?php
    echo $this->element('genericElements/Form/genericForm', [
        'data' => [
            'description' => __('Roles define global rules for a set of users, including first and foremost access controls to certain functionalities.'),
            'model' => 'Roles',
            'fields' => [
                [
                    'field' => 'name'
                ],
                [
                    'field' => 'perm_admin',
                    'type' => 'checkbox',
                    'label' => 'Full admin privilege'
                ],
                [
                    'field' => 'is_default',
                    'type' => 'checkbox',
                    'label' => 'Default role'
                ]
            ],
            'submit' => [
                'action' => $this->request->getParam('action')
            ]
        ]
    ]);
?>
</div>
