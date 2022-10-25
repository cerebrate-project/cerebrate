<?php
    $fields = [
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
            'path' => 'revoked',
            'type' => 'boolean'
        ],
        [
            'key' => __('Key'),
            'path' => 'encryption_key',
            'type' => 'key'
        ]
    ];
    if ($entity['type'] === 'pgp') {
        if (!empty($entity['pgp_fingerprint'])) {
            $fields[] = [
                'key' => __('Fingerprint'),
                'path' => 'pgp_fingerprint'
            ];
        }
        if (!empty($entity['pgp_error'])) {
            $fields[] = [
                'key' => __('PGP Status'),
                'path' => 'pgp_error'
            ];
        } else {
            $fields[] = [
                'key' => __('PGP Status'),
                'raw' => __('OK')
            ];
        }
    }
    echo $this->element(
        '/genericElements/SingleViews/single_view',
        [
            'data' => $entity,
            'fields' => $fields
        ]
    );
