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
                'key' => __('Email'),
                'path' => 'email'
            ],
            [
                'key' => __('UUID'),
                'path' => 'uuid'
            ],
            [
                'key' => __('First Name'),
                'path' => 'first_name'
            ],
            [
                'key' => __('Last Name'),
                'path' => 'last_name'
            ],
            [
                'key' => __('Position'),
                'path' => 'position'
            ],
            [
                'key' => __('Alignments'),
                'type' => 'alignment',
                'path' => '',
                'scope' => 'individuals'
            ]
        ],
        'children' => []
    ]
);
