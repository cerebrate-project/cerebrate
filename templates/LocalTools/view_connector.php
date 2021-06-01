<?php
echo $this->element(
    '/genericElements/SingleViews/single_view',
    [
        'title' => __('{0} connector view', $entity['name']),
        'data' => $entity,
        'fields' => [
            [
                'key' => __('Name'),
                'path' => 'name'
            ],
            [
                'key' => __('Connector name'),
                'path' => 'connector'
            ],
            [
                'key' => __('version'),
                'path' => 'connector_version'
            ],
            [
                'key' => __('Description'),
                'path' => 'connector_description'
            ]
        ],
        'children' => [
            [
                'url' => '/localTools/connectorIndex/',
                'url_params' => ['connector'],
                'title' => __('Connections')
            ]
        ]
    ]
);
