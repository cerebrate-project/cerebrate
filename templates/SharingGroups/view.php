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
                'key' => __('Name'),
                'path' => 'name'
            ],
            [
                'key' => __('Organisation'),
                'path' => 'organisation.name',
                'url' => '/organisations/view/{{0}}',
                'url_vars' => 'organisation.id'
            ],
            [
                'key' => __('Releasability'),
                'path' => 'releasability'
            ],
            [
                'key' => __('Description'),
                'path' => 'description'
            ],
            [
                'key' => __('Active'),
                'path' => 'active',
                'type' => 'boolean'
            ],
            [
                'key' => __('local'),
                'path' => 'local',
                'type' => 'boolean'
            ]
        ],
        'children' => [
            [
                'url' => '/sharingGroups/listOrgs/{{0}}',
                'url_params' => ['id'],
                'title' => __('Organisations')
            ]
        ]
    ]
);
