<?php
echo $this->element(
    '/genericElements/SingleViews/single_view',
    [
        'title' => __('Organisation Group View'),
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
                'key' => __('UUID'),
                'path' => 'uuid'
            ],
            [
                'key' => __('Description'),
                'path' => 'Description'
            ],
            [
                'key' => __('Tags'),
                'type' => 'tags',
                'editable' => $canEdit,
            ]
        ],
        'combinedFieldsView' => false,
        'children' => [
            [
                'url' => '/orgGroups/listAdmins/{{0}}',
                'url_params' => ['id'],
                'title' => __('Administrators')
            ],
            [
                'url' => '/orgGroups/listOrgs/{{0}}',
                'url_params' => ['id'],
                'title' => __('Organisations')
            ]
        ]
    ]
);
