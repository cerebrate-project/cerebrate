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
                'key' => __('Owner'),
                'path' => 'user_id',
                'url' => '/users/view/{{0}}',
                'url_vars' => 'user_id'
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
                'key' => __('Deleted'),
                'path' => 'deleted',
                'type' => 'boolean'
            ]
        ],
        'children' => [
            [
                'url' => '/mailingLists/listIndividuals/{{0}}',
                'url_params' => ['id'],
                'title' => __('Individuals')
            ]
        ]
    ]
);
