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
                    'label' => 'Site admin privilege (instance management)'
                ],
                [
                    'field' => 'perm_community',
                    'type' => 'checkbox',
                    'label' => 'Community admin privilege (data admin)'
                ],
                [
                    'field' => 'perm_group_admin',
                    'type' => 'checkbox',
                    'label' => 'Organisation Group admin privilege'
                ],
                [
                    'field' => 'perm_org_admin',
                    'type' => 'checkbox',
                    'label' => 'Organisation admin privilege'
                ],
                [
                    'field' => 'perm_sync',
                    'type' => 'checkbox',
                    'label' => 'Sync permission'
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
