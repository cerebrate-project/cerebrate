<?php
    $children = [];
    if (!empty($entity['children'])) {
        foreach ($entity['children'] as $child) {
            $children[] = [
                'url' => '/LocalTools/action/{{0}}/' . $child,
                'url_params' => ['id'],
                'title' => \Cake\Utility\Inflector::humanize(substr($child, 0, -6))
            ];
        }
    }
    echo $this->element(
        '/genericElements/SingleViews/single_view',
        [
            'data' => $entity,
            'title' => sprintf(
                '%s control panel using %s',
                h($entity->name),
                h($entity->connector)
            ),
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
                    'key' => __('Connector'),
                    'path' => 'connector'
                ],
                [
                    'key' => __('Exposed'),
                    'path' => 'exposed',
                    'type' => 'boolean'
                ],
                [
                    'key' => __('settings'),
                    'path' => 'settings',
                    'type' => 'json'
                ],
                [
                    'key' => __('Description'),
                    'path' => 'description'
                ]
            ],
            'children' => $children
        ]
    );
