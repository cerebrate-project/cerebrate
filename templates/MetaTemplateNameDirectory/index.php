<?php
use Cake\Utility\Hash;

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
                'name' => '#',
                'sort' => 'id',
                'data_path' => 'id',
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
            ],
            [
                'name' => __('Version'),
                'sort' => 'version',
                'data_path' => 'version',
            ],
            [
                'name' => __('Associated Meta-Template'),
                'sort' => 'meta_template.id',
                'data_path' => 'meta_template.id',
                'element' => 'function',
                'function' => function($row, $viewContext) use ($baseurl) {
                    if (!empty($row->meta_template)) {
                        return $viewContext->Bootstrap->node('a', [
                            'href' => h($baseurl . '/metaTemplates/view/' . $row->meta_template->id ?? ''),
                        ], !empty($row->meta_template->name) ? (sprintf('%s (v%s)', h($row->meta_template->name), h($row->meta_template->version))) :'');
                    } else {
                        return '';
                    }
                }
            ],
        ],
        'title' => __('Meta Template Name Directory'),
        'description' => __('The directory of all meta templates known by the system.'),
        'actions' => []
    ]
]);
