<?php
echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'data' => $data,
        'top_bar' => [
            'children' => [
                [
                    'type' => 'context_filters',
                    'context_filters' => $filteringContexts
                ],
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
                'name' => '#',
                'sort' => 'id',
                'data_path' => 'id',
            ],
            [
                'name' => 'Enabled',
                'sort' => 'enabled',
                'data_path' => 'enabled',
                'element' => 'toggle',
                'url' => '/metaTemplates/toggle/{{0}}',
                'url_params_vars' => ['id'],
                'toggle_data' => [
                    'requirement' => [
                        'function' => function($row, $options) {
                            return true;
                        }
                    ]
                ]
            ],
            [
                'name' => 'Default',
                'sort' => 'is_default',
                'data_path' => 'is_default',
                'element' => 'toggle',
                'url' => '/metaTemplates/toggle/{{0}}/{{1}}',
                'url_params_vars' => [['datapath' => 'id'], ['raw' => 'is_default']],
                'toggle_data' => [
                    'requirement' => [
                        'function' => function($row, $options) {
                            return true;
                        }
                    ],
                    'confirm' => [
                        'enable' => [
                            'titleHtml' => __('Make {{0}} the default template?'),
                            'titleHtml_vars' => ['name'],
                            'bodyHtml' => $this->Html->nestedList([
                                __('Only one template per scope can be set as the default template'),
                                __('Current scope: {{0}}'),
                            ]),
                            'bodyHtml_vars' => ['scope'],
                            'type' => 'confirm-warning',
                            'confirmText' => __('Yes, set as default')
                        ],
                        'disable' => [
                            'titleHtml' => __('Remove {{0}} as the default template?'),
                            'titleHtml_vars' => ['name'],
                            'bodyHtml' => $this->Html->nestedList([
                                __('Current scope: {{0}}'),
                            ]),
                            'bodyHtml_vars' => ['scope'],
                            'type' => 'confirm-warning',
                            'confirmText' => __('Yes, do not set as default')
                        ]
                    ]
                ]
            ],
            [
                'name' => __('Scope'),
                'sort' => 'scope',
                'data_path' => 'scope',
            ],
            [
                'name' => __('Name'),
                'sort' => 'name',
                'data_path' => 'name',
            ],
            [
                'name' => __('Namespace'),
                'sort' => 'namespace',
                'data_path' => 'namespace',
            ],
            [
                'name' => __('UUID'),
                'sort' => 'uuid',
                'data_path' => 'uuid'
            ]
        ],
        'title' => __('Meta Field Templates'),
        'description' => __('The various templates used to enrich certain objects by a set of standardised fields.'),
        'pull' => 'right',
        'actions' => [
            [
                'url' => '/metaTemplates/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye'
            ],
        ]
    ]
]);
?>
