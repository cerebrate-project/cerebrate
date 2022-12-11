<?php
echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'data' => $data,
        'top_bar' => [
            'children' => [
                [
                    'type' => 'search',
                    'button' => __('Search'),
                    'placeholder' => __('Enter value to search'),
                    'data' => '',
                    'searchKey' => 'value'
                ]
            ]
        ],
        'fields' => [
            [
                'name' => __('Field'),
                'sort' => 'field',
                'data_path' => 'field',
            ],
            [
                'name' => __('Type'),
                'sort' => 'type',
                'data_path' => 'type',
            ],
            [
                'name' => __('Multiple values'),
                'sort' => 'multiple',
                'data_path' => 'multiple',
                'field' => 'textarea'
            ],
            [
                'name' => __('Sane defaults'),
                'data_path' => 'sane_default'
            ],
            [
                'name' => __('Values List'),
                'data_path' => 'values_list'
            ],
            [
                'name' => __('Validation regex'),
                'sort' => 'regex',
                'data_path' => 'regex'
            ],
            [
                'name' => __('Field Usage'),
                'sort' => 'counter',
                'data_path' => 'counter',
            ],
        ],
        'title' => __('Meta Template Fields'),
        'description' => __('The various fields that the given template contans. When a meta template is enabled, the fields are automatically appended to the appropriate object.'),
        'pull' => 'right'
    ]
]);
?>
