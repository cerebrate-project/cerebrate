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
                    'field' => 'perm_community_admin',
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
                    'field' => 'perm_meta_field_editor',
                    'type' => 'checkbox',
                    'label' => 'Meta field modification privilege'
                ],
                [
                    'field' => 'perm_view_all_orgs',
                    'type' => 'checkbox',
                    'label' => 'View all organisations'
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
