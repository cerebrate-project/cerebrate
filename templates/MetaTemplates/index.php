<?php
use Cake\Utility\Hash;

if (!empty($updateableTemplates['new'])) {
    $alertHtml = sprintf(
        '<strong>%s</strong> %s',
        __('New meta-templates available!'),
        __n('There is one new template on disk that can be loaded in the database', 'There are {0} new templates on disk that can be loaded in the database:', count($updateableTemplates['new']), count($updateableTemplates['new']))
    );
    $alertList = [];
    $alertList = Hash::extract($updateableTemplates['new'], '{s}.template');
    $alertList = array_map(function($entry) {
        return sprintf('%s:%s %s',
            h($entry['namespace']),
            h($entry['name']),
            $this->Bootstrap->button([
                'variant' => 'link',
                'size' => 'sm',
                'icon' => 'download',
                'title' => __('Create this template'),
                'onclick' => "UI.submissionModalForIndex('/metaTemplates/createNewTemplate/{$entry['uuid']}', '/meta-templates')",
            ])
        );
    }, $alertList);
    $alertHtml .= $this->Html->nestedList($alertList); 
}

echo $this->element('genericElements/IndexTable/index_table', [
    'notice' => !empty($alertHtml) ? ['html' => $alertHtml, 'variant' => 'warning',] : false,
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
                    'button' => __('Search'),
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
                    'editRequirement' => [
                        'function' => function($row, $options) {
                            return true;
                        },
                    ],
                    'skip_full_reload' => true
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
                    'editRequirement' => [
                        'function' => function($row, $options) {
                            return true;
                        }
                    ],
                    'confirm' => [
                        'enable' => [
                            'titleHtml' => __('Make {{0}} the default template?'),
                            'bodyHtml' => $this->Html->nestedList([
                                __('Only one template per scope can be set as the default template'),
                                '{{0}}',
                            ]),
                            'type' => '{{0}}',
                            'confirmText' => __('Yes, set as default'),
                            'arguments' => [
                                'titleHtml' => ['name'],
                                'bodyHtml' => [
                                    [
                                        'function' => function($row, $data) {
                                            $conflictingTemplate = getConflictingTemplate($row, $data);
                                            if (!empty($conflictingTemplate)) {
                                                return sprintf(
                                                    "<span class=\"text-danger fw-bolder\">%s</span> %s.<br />
                                                    <ul><li><span class=\"fw-bolder\">%s</span> %s <span class=\"fw-bolder\">%s</span></li></ul>",
                                                    __('Conflict with:'),
                                                    $this->Html->link(
                                                        h($conflictingTemplate->name),
                                                        '/metaTemplates/view/' . h($conflictingTemplate->id),
                                                        ['target' => '_blank']
                                                    ),
                                                    __('By proceeding'),
                                                    h($conflictingTemplate->name),
                                                    __('will not be the default anymore')
                                                );
                                            }
                                            return __('Current scope: {0}', h($row->scope));
                                        },
                                        'data' => [
                                            'defaultTemplatePerScope' => $defaultTemplatePerScope
                                        ]
                                    ]
                                ],
                                'type' => [
                                    'function' => function($row, $data) {
                                        $conflictingTemplate = getConflictingTemplate($row, $data);
                                        if (!empty($conflictingTemplate)) {
                                            return 'confirm-danger';
                                        }
                                        return 'confirm-warning';
                                    },
                                    'data' => [
                                        'defaultTemplatePerScope' => $defaultTemplatePerScope
                                    ]
                                ]
                            ]
                        ],
                        'disable' => [
                            'titleHtml' => __('Remove {{0}} as the default template?'),
                            'type' => 'confirm-warning',
                            'confirmText' => __('Yes, do not set as default'),
                            'arguments' => [
                                'titleHtml' => ['name'],
                            ]
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
                'name' => __('Version'),
                'sort' => 'version',
                'data_path' => 'version',
            ],
            [
                'name' => __('UUID'),
                'sort' => 'uuid',
                'data_path' => 'uuid'
            ],
        ],
        'title' => __('Meta Field Templates'),
        'description' => __('The various templates used to enrich certain objects by a set of standardised fields.'),
        'includeAllPagination' => true,
        'actions' => [
            [
                'url' => '/metaTemplates/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye'
            ],
            [
                'open_modal' => '/metaTemplates/update/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'title' => __('Update Meta-Template'),
                'icon' => 'download',
                'complex_requirement' => [
                    'function' => function ($row, $options) {
                        return empty($row['updateStatus']['up-to-date']) && empty($row['updateStatus']['to-existing']);
                    }
                ]
            ],
            [
                'open_modal' => '/metaTemplates/getMetaFieldsToUpdate/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'title' => __('Get meta-fields that should be moved to the newest version of this meta-template'),
                'icon' => 'exclamation-triangle',
                'variant' => 'warning',
                'complex_requirement' => [
                    'function' => function ($row, $options) {
                        return !empty($row['updateStatus']['to-existing']) && empty($row['updateStatus']['can-be-removed']);
                    }
                ]
            ],
            [
                'open_modal' => '/metaTemplates/migrateMetafieldsToNewestTemplate/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'title' => __('Update meta-fields to the newest version of this meta-template'),
                'icon' => 'arrow-circle-up',
                'variant' => 'success',
                'complex_requirement' => [
                    'function' => function ($row, $options) {
                        return !empty($row['updateStatus']['to-existing']) && empty($row['updateStatus']['can-be-removed']);
                    }
                ]
            ],
            [
                'open_modal' => '/metaTemplates/delete/[onclick_params_data_path]',
                'modal_params_data_path' => 'id',
                'title' => __('This meta-template doesn\'t have any meta-fields and can be safely removed.'),
                'icon' => 'trash',
                'variant' => 'success',
                'complex_requirement' => [
                    'function' => function ($row, $options) {
                        return !empty($row['updateStatus']['to-existing']) && !empty($row['updateStatus']['can-be-removed']);
                    }
                ]
            ],
        ]
    ]
]);

function getConflictingTemplate($row, $data) {
    if (!empty($data['data']['defaultTemplatePerScope'][$row->scope])) {
        $conflictingTemplate = $data['data']['defaultTemplatePerScope'][$row->scope];
        if (!empty($conflictingTemplate)) {
            return $conflictingTemplate;
        }
    }
    return [];
}
