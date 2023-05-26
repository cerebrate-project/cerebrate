<?php
    echo $this->element('genericElements/Form/genericForm', [
        'data' => [
            'description' => __('Roles define global rules for a set of users, including first and foremost access controls to certain functionalities.'),
            'model' => 'EnumerationCollections',
            'fields' => [
                [
                    'field' => 'name',
                    'label' => __('Name')
                ],
                [
                    'field' => 'enabled',
                    'label' => __('Enabled'),
                    'type' => 'checkbox',
                ],
                [
                    'field' => 'target_model',
                    'label' => __('Model'),
                ],
                [
                    'field' => 'target_field',
                    'label' => __('Field'),
                ],
                [
                    'field' => 'description',
                    'label' => __('Description'),
                ],
                [
                    'field' => 'values',
                    'label' => __('Values'),
                    'type' => 'textarea'
                ],
            ],
            'submit' => [
                'action' => $this->request->getParam('action')
            ]
        ]
    ]);
?>
</div>
