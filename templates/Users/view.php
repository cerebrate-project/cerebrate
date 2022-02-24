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
                'type' => 'generic',
                'key' => __('Email'),
                'path' => 'individual.email',
                'url' => '/individuals/view/{{0}}',
                'url_vars' => 'individual_id'
            ],
            [
                'type' => 'generic',
                'key' => __('Organisation'),
                'path' => 'organisation.name',
                'url' => '/organisations/view/{{0}}',
                'url_vars' => 'organisation.id'
            ],
            [
                'type' => 'generic',
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
                'url' => '/AuthKeys/index?Users.id={{0}}',
                'url_params' => ['id'],
                'title' => __('Authentication keys')
            ],
            [
                'url' => '/EncryptionKeys/index?owner_id={{0}}',
                'url_params' => ['individual_id'],
                'title' => __('Encryption keys')
            ],
            [
                'url' => '/UserSettings/index?Users.id={{0}}',
                'url_params' => ['id'],
                'title' => __('User settings')
            ]
        ]
    ]
);
