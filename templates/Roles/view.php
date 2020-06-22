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
                'key' => __('Admin permission'),
                'path' => 'perm_admin',
                'type' => 'boolean'
            ],
            [
                'key' => __('Default role'),
                'path' => 'is_default',
                'type' => 'boolean'
            ]
        ],
        'children' => []
    ]
);
