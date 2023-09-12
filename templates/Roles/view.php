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
                'key' => __('Organisation Group admin permission'),
                'path' => 'perm_group_admin',
                'type' => 'boolean'
            ],
            [
                'key' => __('Organisation admin permission'),
                'path' => 'perm_org_admin',
                'type' => 'boolean'
            ],
            [
                'key' => __('Sync permission'),
                'path' => 'perm_sync',
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
