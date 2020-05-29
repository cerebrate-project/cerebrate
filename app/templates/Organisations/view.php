<?php
echo $this->element(
    '/genericElements/SingleViews/single_view',
    array(
        'title' => __('Organisation View'),
        'data' => $organisation,
        'fields' => array(
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
                'key' => __('URL'),
                'path' => 'url'
            ],
            [
                'key' => __('Nationality'),
                'path' => 'nationality'
            ],
            [
                'key' => __('Sector'),
                'path' => 'sector'
            ],
            [
                'key' => __('Type'),
                'path' => 'type'
            ],
            [
                'key' => __('Contacts'),
                'path' => 'contacts'
            ],
            [
                'key' => __('Alignments'),
                'type' => 'alignment',
                'path' => 'alignments',
                'scope' => 'organisations'
            ]
        ),
        'children' => []
    )
);
