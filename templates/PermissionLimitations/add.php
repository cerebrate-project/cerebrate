<?php
    echo $this->element('genericElements/Form/genericForm', [
        'data' => [
            'description' => __(
                'Add a limitation of how many users can have the given permission. The scope applies the limitation globally or for a given organisation. 
                Permissions can be valid role permissions or any user meta field. 
                An example: perm_misp global limit 500, organisation limit 10 would ensure that there are a maximum of 500 MISP admitted users on the instance, limiting the number of users to 10 / org.'
            ),
            'model' => 'PermissionLimitation',
            'fields' => [
                [
                    'field' => 'scope',
                    'type' => 'dropdown',
                    'label' => 'Scope',
                    'options' => [
                        'global' => 'global',
                        'organisation' => 'organisation'
                    ]
                ],
                [
                    'field' => 'permission'
                ],
                [
                    'field' => 'max_occurrence',
                    'label' => 'Limit'
                ],
                [
                    'field' => 'comment',
                    'label' => 'Comment'
                ]
            ],
            'submit' => [
                'action' => $this->request->getParam('action')
            ]
        ]
    ]);
?>
</div>
