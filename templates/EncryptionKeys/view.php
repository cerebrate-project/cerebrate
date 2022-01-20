<?php
echo $this->element(
    '/genericElements/SingleViews/single_view',
    [
        'data' => $entity,
        'fields' => [
            [
                'key' => __('ID'),
                'path' => 'id'
            ],
            [
                'key' => __('Type'),
                'path' => 'type'
            ],
            [
                'key' => __('Owner'),
                'path' => 'owner_id',
                'owner_model_path' => 'owner_model',
                'type' => 'owner'
            ],
            [
                'key' => __('Revoked'),
                'path' => 'revoked'
            ],

            [
                'key' => __('Key'),
                'path' => 'encryption_key'
            ]
        ]
    ]
);
