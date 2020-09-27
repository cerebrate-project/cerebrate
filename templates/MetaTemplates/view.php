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
                'key' => __('Namespace'),
                'path' => 'namespace'
            ],
            [
                'key' => __('Description'),
                'path' => 'description'
            ],
            [
                'key' => __('Version'),
                'path' => 'version'
            ],
            [
                'key' => __('Source'),
                'path' => 'source'
            ]
        ],
        'children' => [
            [
                'url' => '/MetaTemplateFields/index?meta_template_id={{0}}',
                'url_params' => ['id'],
                'title' => __('Fields')
            ]
        ]
    ]
);
