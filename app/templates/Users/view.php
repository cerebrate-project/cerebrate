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
                'key' => __('UUID'),
                'path' => 'uuid'
            ],
            [
                'key' => __('Username'),
                'path' => 'username'
            ],
            [
                'key' => __('Email'),
                'path' => 'individual.email'
            ],
            [
                'key' => __('Role'),
                'path' => 'role.name',
                'url' => '/roles/view/{{0}}',
                'url_vars' => 'role.id'
            ],
            [
                'key' => __('First name'),
                'path' => 'individual.first_name'
            ],
            [
                'key' => __('Last name'),
                'path' => 'individual.last_name'
            ],
            [
                'key' => __('Alignments'),
                'type' => 'alignment',
                'path' => 'individual',
                'scope' => 'individuals'
            ]
        ],
        'children' => [
            [
                'url' => '/AuthKeys/index?users_id={{0}}',
                'url_params' => ['id'],
                'title' => __('Authentication keys')
            ],
            [
                'url' => '/EncryptionKeys/index?users_id={{0}}',
                'url_params' => ['id'],
                'title' => __('Encryption keys')
            ]
        ]
    ]
);
