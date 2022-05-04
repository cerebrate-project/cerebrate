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
                'name' => __('Name'),
                'sort' => 'name',
                'data_path' => 'name',
            ],
            [
                'name' => 'Connector',
                'data_path' => 'connector'
            ],
            [
                'name' => 'Version',
                'data_path' => 'connector_version'
            ],
            [
                'name' => 'Description',
                'data_path' => 'connector_description'
            ],
            [
                'name' => 'Connections',
                'data_path' => 'connections',
                'element' => 'health',
                'class' => 'text-nowrap'
            ]
        ],
        'title' => __('Local tool connector index'),
        'description' => __('Cerebrate can connect to local tools via individual connectors, built to expose the various functionalities of the given tool via Cerebrate. Simply view the connectors\' details and the accompanying instance list to manage the connections using the given connector. '),
        'pull' => 'right',
        'actions' => [
            [
                'url' => '/localTools/viewConnector',
                'url_params_data_paths' => ['connector'],
                'icon' => 'eye'
            ]
        ]
    ]
]);
echo '</div>';
?>
