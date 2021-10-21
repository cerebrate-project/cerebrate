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
                'key' => __('Value'),
                'path' => 'value'
            ],
            [
                'key' => __('Created'),
                'path' => 'created'
            ],
            [
                'key' => __('Modified'),
                'path' => 'modified'
            ],
            [
                'key' => __('User'),
                'path' => 'user.username',
                'url' => '/users/view/{{0}}',
                'url_vars' => 'user.id'
            ],
        ],
    ]
);
