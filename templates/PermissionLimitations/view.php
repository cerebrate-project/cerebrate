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
                'key' => __('Scope'),
                'path' => 'scope'
            ],
            [
                'key' => __('Permission'),
                'path' => 'permission'
            ],
            [
                'key' => __('Limit'),
                'path' => 'max_occurrence'
            ],
            [
                'key' => __('Comment'),
                'path' => 'comment'
            ]
        ],
        'children' => []
    ]
);
