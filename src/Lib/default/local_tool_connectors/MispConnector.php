<?php
namespace MispConnector;
require_once(ROOT . '/src/Lib/default/local_tool_connectors/CommonConnectorTools.php');
use CommonConnectorTools\CommonConnectorTools;
use Cake\Http\Client;

class MispConnector extends CommonConnectorTools
{
    public $description = 'MISP connector, handling diagnostics, organisation and sharing group management of your instance. Synchronisation requests can also be managed through the connector.';
    public $name = 'MISP';
    public $exposedFunctions = [
        'diagnostics',
        'viewOrgAction'
    ];
    public $version = '0.1';

    public function addExposedFunction(string $functionName): void
    {
        $this->exposedFunctions[] = $functionName;
    }

    public function health(Object $connection): array
    {
        $settings = json_decode($connection->settings, true);
        $http = new Client();
        $response = $http->post($settings['url'] . '/users/view/me.json', '{}', [
            'headers' => [
                'AUTHORIZATION' => $settings['authkey'],
                'Accept' => 'Application/json',
                'Content-type' => 'Application/json'
             ]
        ]);
        $responseCode = $response->getStatusCode();
        if ($response->isOk()) {
            $status = 1;
            $message = __('OK');
        } else if ($responseCode == 403){
            $status = 3;
            $message = __('Unauthorized');
        } else {
            $status = 0;
            $message = __('Something went wrong.');
        }

        return [
            'status' => $status,
            'message' => $message
        ];
    }

    private function viewOrgAction(array $params): array
    {
        if (empty($params['connection'])) {
            throw new InvalidArgumentException(__('No connection object received.'));
        }
        $settings = json_decode($params['connection']->settings, true);
        $http = new Client();
        $url = '/users/organisations/index/scope:all';
        if (!empty($params['page'])) {
            $url .= '/page:' . $params['page'];
        }
        if (!empty($params['limit'])) {
            $url .= '/limit:' . $params['limit'];
        }
        if (!empty($params['quickFilter'])) {
            $url .= '/searchall:' . $params['quickFilter'];
        }
        $response = $http->post($settings['url'] . '/users/organisations/index/scope:all', '{}', [
            'headers' => [
                'AUTHORIZATION' => $settings['authkey'],
                'Accept' => 'Application/json',
                'Content-type' => 'Application/json'
             ]
        ]);
        $responseCode = $response->getStatusCode();
        if ($response->isOk()) {
            return [
                'type' => 'index',
                'data' => [
                    'data' => json_decode($response->getBody(), true),
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
                            'name' => '#',
                            'sort' => 'id',
                            'data_path' => 'Organisation.id',
                        ],
                        [
                            'name' => __('Name'),
                            'sort' => 'name',
                            'data_path' => 'Organisation.name',
                        ],
                        [
                            'name' => __('UUID'),
                            'sort' => 'uuid',
                            'data_path' => 'Organisation.uuid',
                        ]
                    ],
                    'title' => false,
                    'description' => false,
                    'pull' => 'right',
                    'actions' => [
                        [
                            'url' => '/localTools/action/fetchOrg',
                            'url_params_data_paths' => ['id'],
                            'icon' => 'download'
                        ]
                    ]
                ]
            ];
        } else {
            return __('Could not fetch the organisations, error code: {0}', $response->getStatusCode());
        }
    }
}

 ?>
