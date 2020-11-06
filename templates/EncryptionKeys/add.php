<?php
echo $this->element('genericElements/Form/genericForm', [
    'data' => [
        'title' => __('Add new encryption key'),
        'description' => __('Alignments indicate that an individual belongs to an organisation in one way or another. The type of relationship is defined by the type field.'),
        'model' => 'Organisations',
        'fields' => [
            [
                'field' => 'owner_type',
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
                    'source' => '#owner_type-field',
                    'option' => 'organisation'
                ]
            ],
            [
                'field' => 'individual_id',
                'label' => __('Owner individual'),
                'options' => $dropdownData['individual'] ?? [],
                'type' => 'dropdown',
                'stateDependence' => [
                    'source' => '#owner_type-field',
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
