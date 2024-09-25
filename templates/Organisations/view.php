<?php

$fields = [
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
        'key' => __('Country'),
        'type' => 'country',
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
        'key' => __('Tags'),
        'type' => 'tags',
        'editable' => $canEdit,
    ],
    [
        'key' => __('Alignments'),
        'type' => 'alignment',
        'path' => '',
        'scope' => 'organisations'
    ]
];

if (!empty($entity['org_groups'])) {
    $fields[] = [
        'type' => 'link_list',
        'key' => __('Group memberships'),
        'path' => 'org_groups',
        'data_id_sub_path' => 'id',
        'data_value_sub_path' => 'name',
        'url_pattern' => '/orgGroups/view/{{data_id}}'
    ];
}
echo $this->element(
    '/genericElements/SingleViews/single_view',
    [
        'title' => __('Organisation View'),
        'data' => $entity,
        'fields' => $fields,
        'combinedFieldsView' => false,
        'children' => [
            [
                'url' => '/EncryptionKeys/index?owner_id={{0}}&owner_model=organisation',
                'url_params' => ['id'],
                'title' => __('Encryption keys')
            ]
        ]
    ]
);
