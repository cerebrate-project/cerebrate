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
                'key' => __('Name'),
                'path' => 'name'
            ],
            [
                'key' => __('Enabled'),
                'path' => 'enabled',
                'type' => 'boolean'
            ],
            [
                'key' => __('UUID'),
                'path' => 'uuid'
            ],
            [
                'key' => __('Model'),
                'path' => 'target_model'
            ],
            [
                'key' => __('Field'),
                'path' => 'target_field'
            ],
            [
                'key' => __('Description'),
                'path' => 'description'
            ],
            
        ],
        'children' => [
            [
                'url' => '/Enumerations/index?EnumerationCollection.id={{0}}',
                'url_params' => ['id'],
                'title' => __('Values')
            ]
        ]
    ]
);
