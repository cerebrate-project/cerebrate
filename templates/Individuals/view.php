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
                'key' => __('Email'),
                'path' => 'email'
            ],
            [
                'key' => __('UUID'),
                'path' => 'uuid'
            ],
            [
                'key' => __('First Name'),
                'path' => 'first_name'
            ],
            [
                'key' => __('Last Name'),
                'path' => 'last_name'
            ],
            [
                'key' => __('Position'),
                'path' => 'position'
            ],
            [
                'key' => __('Tags'),
                'type' => 'tags',
                'editable' => $canEdit,
            ],
            [
                'key' => __('Has associated user'),
                'type' => 'boolean',
                'path' => 'has_user',
                'requirement' => !$canEdit
            ],
            [
                'key' => __('Associated user'),
                'type' => 'user',
                'owner_model_path' => 'user',
                'path' => 'user',
                'requirement' => $canEdit
            ],
            [
                'key' => __('Alignments'),
                'type' => 'alignment',
                'path' => '',
                'scope' => 'individuals'
            ]
        ],
        'children' => [
            [
                'url' => '/EncryptionKeys/index?owner_id={{0}}&owner_model=individual',
                'url_params' => ['id'],
                'title' => __('Encryption keys')
            ]
        ]
    ]
);
