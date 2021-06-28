<?php
echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'data' => $data,
        'top_bar' => [
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
                'name' => __('Id'),
                'data_path' => 'id',
            ],
            [
                'name' => __('Name'),
                'data_path' => 'name',
            ],
            [
                'name' => 'Connector',
                'data_path' => 'connector'
            ],
            [
                'name' => __('Description'),
                'data_path' => 'description',
            ],
            [
                'name' => __('Connected Local Tools'),
                'data_path' => 'local_tool',
                'element' => 'local_tools_status'
            ]
        ],
        'title' => __('Local tools made available by the remote Cerebrate'),
        'description' => __('Cerebrate can connect to local tools via individual connectors and administrators can choose to expose a subset of their tools to other members of their Cerebrate in order for their peers to be able to issue interconnection requests. '),
        'pull' => 'right',
        'skip_pagination' => 1,
        'actions' => [
            [
                'open_modal' => sprintf('/localTools/connectionRequest/%s/[onclick_params_data_path]', h($id)),
                'reload_url' => $this->Url->build(['action' => 'broodTools', $id]),
                'modal_params_data_path' => 'id',
                'title' => 'Issue a connection request',
                'icon' => 'plug'
            ]
        ]
    ]
]);
echo '</div>';
?>
