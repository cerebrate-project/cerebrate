<?php
echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'data' => $data,
        'top_bar' => [
            'pull' => 'right',
            'children' => [
                [
                    'type' => 'search',
                    'button' => __('Filter'),
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
                'name' => __('Validation regex'),
                'sort' => 'regex',
                'data_path' => 'regex'
            ]
        ],
        'title' => __('Meta Template Fields'),
        'description' => __('The various fields that the given template contans. When a meta template is enabled, the fields are automatically appended to the appropriate object.'),
        'pull' => 'right'
    ]
]);
?>
