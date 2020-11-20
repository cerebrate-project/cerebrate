<?php
echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'row_modifier' => function(App\Model\Entity\EncryptionKey $row): string {
            return !empty($row['revoked']) ? 'text-light bg-secondary' : '';
        },
        'data' => $data,
        'top_bar' => [
            'pull' => 'right',
            'children' => [
                [
                    'type' => 'simple',
                    'children' => [
                        'data' => [
                            'type' => 'simple',
                            'text' => __('Add encryption key'),
                            'class' => 'btn btn-primary',
                            'popover_url' => '/encryptionKeys/add'
                        ]
                    ]
                ],
                [
                    'type' => 'search',
                    'button' => __('Filter'),
                    'placeholder' => __('Enter value to search'),
                    'data' => '',
                    'searchKey' => 'value'
                ]
            ]
        ],
        'fields' => [
            [
                'name' => '#',
                'sort' => 'id',
                'data_path' => 'id',
            ],
            [
                'name' => __('Type'),
                'sort' => 'type',
                'data_path' => 'type',
            ],
            [
                'name' => __('Owner'),
                'data_path' => 'owner_id',
                'owner_type_path' => 'owner_type',
                'element' => 'owner'
            ],
            [
                'name' => __('Revoked'),
                'sort' => 'revoked',
                'data_path' => 'revoked',
                'element' => 'boolean'
            ],
            [
                'name' => __('Key'),
                'data_path' => 'encryption_key'
            ],
        ],
        'title' => __('Encryption key Index'),
        'description' => __('A list encryption / signing keys that are bound to an individual or an organsiation.'),
        'pull' => 'right',
        'actions' => [
            [
                'onclick' => 'populateAndLoadModal(\'/encryptionKeys/view/[onclick_params_data_path]\');',
                'onclick_params_data_path' => 'id',
                'icon' => 'eye'
            ],
            [
                'onclick' => 'populateAndLoadModal(\'/encryptionKeys/edit/[onclick_params_data_path]\');',
                'onclick_params_data_path' => 'id',
                'icon' => 'edit'
            ],
            [
                'onclick' => 'populateAndLoadModal(\'/encryptionKeys/delete/[onclick_params_data_path]\');',
                'onclick_params_data_path' => 'id',
                'icon' => 'trash'
            ]
        ]
    ]
]);
