<?php

echo $this->element(
    '/genericElements/SingleViews/single_view',
    [
        'title' => __('Cererate Brood View'),
        'data' => $entity,
        'fields' => [
            [
                'key' => __('ID'),
                'path' => 'id'
            ],
            [
                'key' => __('Name'),
                'path' => 'name'
            ],
            [
                'key' => __('Url'),
                'path' => 'url'
            ],
            [
                'key' => __('Description'),
                'path' => 'description'
            ],
            [
                'key' => __('Owner'),
                'path' => 'organisation.name'
            ]
        ],
        'metaFields' => empty($metaFields) ? [] : $metaFields,
        'children' => [
            [
                'url' => '/Broods/previewIndex/{{0}}/organisations',
                'url_params' => ['id'],
                'title' => __('Organisations')
            ],
            [
                'url' => '/Broods/previewIndex/{{0}}/individuals',
                'url_params' => ['id'],
                'title' => __('Individuals')
            ],
            [
                'url' => '/Broods/previewIndex/{{0}}/sharingGroups',
                'url_params' => ['id'],
                'title' => __('Sharing groups')
            ]
        ]
    ]
);
