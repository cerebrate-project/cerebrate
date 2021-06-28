<?php
echo $this->element('genericElements/Form/genericForm', [
    'data' => [
        'title' => __('Add new encryption key'),
        'description' => __('Assign encryption keys to the user, used to securely communicate or validate messages coming from the user.'),
        'model' => 'Organisations',
        'fields' => [
            [
                'field' => 'owner_model',
                'label' => __('Owner type'),
                'options' => array_combine(array_keys($dropdownData), array_keys($dropdownData)),
                'type' => 'dropdown'
            ],
            [
                'field' => 'organisation_id',
                'label' => __('Owner organisation'),
                'options' => $dropdownData['organisation'] ?? [],
                'type' => 'dropdown',
                'stateDependence' => [
                    'source' => '#owner_model-field',
                    'option' => 'organisation'
                ]
            ],
            [
                'field' => 'individual_id',
                'label' => __('Owner individual'),
                'options' => $dropdownData['individual'] ?? [],
                'type' => 'dropdown',
                'stateDependence' => [
                    'source' => '#owner_model-field',
                    'option' => 'individual'
                ]
            ],
            [
                'field' => 'uuid',
                'type' => 'uuid'
            ],
            [
                'field' => 'type',
                'options' => ['pgp' => 'PGP', 'smime' => 'S/MIME'],
                'type' => 'dropdown'
            ],
            [
                'field' => 'encryption_key',
                'label' => __('Public key'),
                'type' => 'textarea',
                'rows' => 8
            ],
            [
                'field' => 'revoked',
                'label' => __('Revoked'),
                'type' => 'checkbox'
            ]
        ],
        'submit' => [
            'action' => $this->request->getParam('action')
        ]
    ]
]);
