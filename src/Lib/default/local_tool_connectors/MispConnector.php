<?php
namespace MispConnector;
require_once(ROOT . '/src/Lib/default/local_tool_connectors/CommonConnectorTools.php');
use CommonConnectorTools\CommonConnectorTools;
use Cake\Http\Client;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Client\Response;

class MispConnector extends CommonConnectorTools
{
    public $description = 'MISP connector, handling diagnostics, organisation and sharing group management of your instance. Synchronisation requests can also be managed through the connector.';

    public $name = 'MISP';

    public $exposedFunctions = [
        'serverSettingsAction' => [
            'type' => 'index',
            'scope' => 'child',
            'params' => [
                'quickFilter',
                'sort',
                'direction',
                'page',
                'limit'
            ]
        ],
        'organisationsAction' => [
            'type' => 'index',
            'scope' => 'child',
            'params' => [
                'quickFilter',
                'limit',
                'page',
                'sort',
                'direction'
            ]
        ],
        'sharingGroupsAction' => [
            'type' => 'index',
            'scope' => 'child',
            'params' => [
                'quickFilter',
                'limit',
                'page',
                'sort',
                'direction'
            ]
        ],
        'fetchOrganisationAction' => [
            'type' => 'formAction',
            'scope' => 'childAction',
            'params' => [
                'uuid'
            ],
            'redirect' => 'organisationsAction'
        ],
        'fetchSharingGroupAction' => [
            'type' => 'formAction',
            'scope' => 'childAction',
            'params' => [
                'uuid'
            ],
            'redirect' => 'sharingGroupsAction'
        ],
        'modifySettingAction' => [
            'type' => 'formAction',
            'scope' => 'childAction',
            'params' => [
                'setting',
                'value'
            ],
            'redirect' => 'serverSettingsAction'
        ]
    ];
    public $version = '0.1';

    public function addExposedFunction(string $functionName): void
    {
        $this->exposedFunctions[] = $functionName;
    }

    public function getExposedFunction(string $functionName): array
    {
        if (!empty($this->exposedFunctions[$functionName])) {
            return $exposedFunctions[$functionName];
        } else {
            throw new NotFoundException(__('Invalid action requested.'));
        }
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

    private function getData(string $url, array $params): Response
    {
        if (empty($params['connection'])) {
            throw new NotFoundException(__('No connection object received.'));
        }
        $settings = json_decode($params['connection']->settings, true);
        $http = new Client();
        if (!empty($params['sort'])) {
            $list = explode('.', $params['sort']);
            $params['sort'] = end($list);
        }
        if (!isset($params['limit'])) {
            $params['limit'] = 50;
        }
        $url = $this->urlAppendParams($url, $params);
        $response = $http->get($settings['url'] . $url, false, [
            'headers' => [
                'AUTHORIZATION' => $settings['authkey'],
                'Accept' => 'application/json',
                'Content-type' => 'application/json'
             ]
        ]);
        if ($response->isOk()) {
            return $response;
        } else {
            throw new NotFoundException(__('Could not retrieve the requested resource.'));
        }
    }

    private function postData(string $url, array $params): Response
    {
        if (empty($params['connection'])) {
            throw new NotFoundException(__('No connection object received.'));
        }
        $settings = json_decode($params['connection']->settings, true);
        $http = new Client();
        $url = $this->urlAppendParams($url, $params);
        $response = $http->post($settings['url'] . $url, json_encode($params['body']), [
            'headers' => [
                'AUTHORIZATION' => $settings['authkey'],
                'Accept' => 'application/json'
            ],
            'type' => 'json'
        ]);
        if ($response->isOk()) {
            return $response;
        } else {
            throw new NotFoundException(__('Could not post to the requested resource.'));
        }
    }

    public function urlAppendParams(string $url, array $params): string
    {
        if (!isset($params['validParams'])) {
            $validParams = [
                'quickFilter' => 'searchall',
                'sort' => 'sort',
                'page' => 'page',
                'direction' => 'direction',
                'limit' => 'limit'
            ];
        } else {
            $validParams = $params['validParams'];
        }
        foreach ($validParams as $param => $remoteParam) {
            if (!empty($params[$param])) {
                $url .= sprintf('/%s:%s', $remoteParam, $params[$param]);
            }
        }
        return $url;
    }


    public function diagnosticsAction(array $params): array
    {

    }

    public function serverSettingsAction(array $params): array
    {
        $params['validParams'] = [
            'limit' => 'limit',
            'page' => 'page',
            'quickFilter' => 'searchall'
        ];
        $urlParams = h($params['connection']['id']) . '/serverSettingsAction';
        $response = $this->getData('/servers/serverSettings', $params);
        $data = $response->getJson();
        if (!empty($data['finalSettings'])) {
            $finalSettings = [
                'type' => 'index',
                'data' => [
                    'data' => $data['finalSettings'],
                    'skip_pagination' => 1,
                    'top_bar' => [
                        'children' => [
                            [
                                'type' => 'search',
                                'button' => __('Filter'),
                                'placeholder' => __('Enter value to search'),
                                'data' => '',
                                'searchKey' => 'value',
                                'additionalUrlParams' => $urlParams
                            ]
                        ]
                    ],
                    'fields' => [
                        [
                            'name' => 'Setting',
                            'sort' => 'setting',
                            'data_path' => 'setting',
                        ],
                        [
                            'name' => 'Criticality',
                            'sort' => 'level',
                            'data_path' => 'level',
                            'arrayData' => [
                                0 => 'Critical',
                                1 => 'Recommended',
                                2 => 'Optional'
                            ],
                            'element' => 'array_lookup_field'
                        ],
                        [
                            'name' => __('Value'),
                            'sort' => 'value',
                            'data_path' => 'value',
                        ],
                        [
                            'name' => __('Type'),
                            'sort' => 'type',
                            'data_path' => 'type',
                        ],
                        [
                            'name' => __('Error message'),
                            'sort' => 'errorMessage',
                            'data_path' => 'errorMessage',
                        ]
                    ],
                    'title' => false,
                    'description' => false,
                    'pull' => 'right',
                    'actions' => [
                        [
                            'open_modal' => '/localTools/action/' . h($params['connection']['id']) . '/modifySettingAction?setting={{0}}',
                            'modal_params_data_path' => ['setting'],
                            'icon' => 'download',
                            'reload_url' => '/localTools/action/' . h($params['connection']['id']) . '/ServerSettingsAction'
                        ]
                    ]
                ]
            ];
            if (!empty($params['quickFilter'])) {
                $needle = strtolower($params['quickFilter']);
                foreach ($finalSettings['data']['data'] as $k => $v) {
                    if (strpos(strtolower($v['setting']), $needle) === false) {
                        unset($finalSettings['data']['data'][$k]);
                    }
                }
                $finalSettings['data']['data'] = array_values($finalSettings['data']['data']);
            }
            return $finalSettings;
        } else {
            return [];
        }
    }

    public function organisationsAction(array $params): array
    {
        $params['validParams'] = [
            'limit' => 'limit',
            'page' => 'page',
            'quickFilter' => 'searchall'
        ];
        $urlParams = h($params['connection']['id']) . '/organisationsAction';
        $response = $this->getData('/organisations/index', $params);
        $data = $response->getJson();
        if (!empty($data)) {
            return [
                'type' => 'index',
                'data' => [
                    'data' => $data,
                    'skip_pagination' => 1,
                    'top_bar' => [
                        'children' => [
                            [
                                'type' => 'search',
                                'button' => __('Filter'),
                                'placeholder' => __('Enter value to search'),
                                'data' => '',
                                'searchKey' => 'value',
                                'additionalUrlParams' => $urlParams
                            ]
                        ]
                    ],
                    'fields' => [
                        [
                            'name' => 'Name',
                            'sort' => 'Organisation.name',
                            'data_path' => 'Organisation.name',
                        ],
                        [
                            'name' => 'uuid',
                            'sort' => 'Organisation.uuid',
                            'data_path' => 'Organisation.uuid'
                        ],
                        [
                            'name' => 'nationality',
                            'sort' => 'Organisation.nationality',
                            'data_path' => 'Organisation.nationality'
                        ],
                        [
                            'name' => 'sector',
                            'sort' => 'Organisation.sector',
                            'data_path' => 'Organisation.sector'
                        ]
                    ],
                    'title' => false,
                    'description' => false,
                    'pull' => 'right',
                    'actions' => [
                        [
                            'open_modal' => '/localTools/action/' . h($params['connection']['id']) . '/fetchOrganisationAction?uuid={{0}}',
                            'modal_params_data_path' => ['Organisation.uuid'],
                            'icon' => 'download',
                            'reload_url' => '/localTools/action/' . h($params['connection']['id']) . '/organisationsAction'
                        ]
                    ]
                ]
            ];
        } else {
            return [];
        }
    }

    public function sharingGroupsAction(array $params): array
    {
        $params['validParams'] = [
            'limit' => 'limit',
            'page' => 'page',
            'quickFilter' => 'searchall'
        ];
        $urlParams = h($params['connection']['id']) . '/sharingGroupsAction';
        $response = $this->getData('/sharing_groups/index', $params);
        $data = $response->getJson();
        if (!empty($data)) {
            return [
                'type' => 'index',
                'data' => [
                    'data' => $data['response'],
                    'skip_pagination' => 1,
                    'top_bar' => [
                        'children' => [
                            [
                                'type' => 'search',
                                'button' => __('Filter'),
                                'placeholder' => __('Enter value to search'),
                                'data' => '',
                                'searchKey' => 'value',
                                'additionalUrlParams' => $urlParams
                            ]
                        ]
                    ],
                    'fields' => [
                        [
                            'name' => 'Name',
                            'sort' => 'SharingGroup.name',
                            'data_path' => 'SharingGroup.name',
                        ],
                        [
                            'name' => 'uuid',
                            'sort' => 'SharingGroup.uuid',
                            'data_path' => 'SharingGroup.uuid'
                        ],
                        [
                            'name' => 'Organisations',
                            'sort' => 'Organisation',
                            'data_path' => 'Organisation',
                            'element' => 'count_summary'
                        ],
                        [
                            'name' => 'Roaming',
                            'sort' => 'SharingGroup.roaming',
                            'data_path' => 'SharingGroup.roaming',
                            'element' => 'boolean'
                        ],
                        [
                            'name' => 'External servers',
                            'sort' => 'Server',
                            'data_path' => 'Server',
                            'element' => 'count_summary'
                        ]
                    ],
                    'title' => false,
                    'description' => false,
                    'pull' => 'right',
                    'actions' => [
                        [
                            'open_modal' => '/localTools/action/' . h($params['connection']['id']) . '/fetchSharingGroupAction?uuid={{0}}',
                            'modal_params_data_path' => ['SharingGroup.uuid'],
                            'icon' => 'download',
                            'reload_url' => '/localTools/action/' . h($params['connection']['id']) . '/SharingGroupsAction'
                        ]
                    ]
                ]
            ];
        } else {
            return [];
        }
    }

    public function fetchOrganisationAction(array $params): array
    {
        if ($params['request']->is(['get'])) {
            return [
                'data' => [
                    'title' => __('Fetch organisation'),
                    'description' => __('Fetch and create/update organisation ({0}) from MISP.', $params['uuid']),
                    'submit' => [
                        'action' => $params['request']->getParam('action')
                    ],
                    'url' => ['controller' => 'localTools', 'action' => 'action', $params['connection']['id'], 'fetchOrganisationAction', $params['uuid']]
                ]
            ];
        } elseif ($params['request']->is(['post'])) {
            $response = $this->getData('/organisations/view/' . $params['uuid'], $params);
            if ($response->getStatusCode() == 200) {
                $result = $this->captureOrganisation($response->getJson()['Organisation']);
                if ($result) {
                    return ['success' => 1, 'message' => __('Organisation created/modified.')];
                } else {
                    return ['success' => 0, 'message' => __('Could not save the changes to the organisation.')];
                }
            } else {
                return ['success' => 0, 'message' => __('Could not fetch the remote organisation.')];
            }
        }
        throw new MethodNotAllowedException(__('Invalid http request type for the given action.'));
    }

    public function fetchSharingGroupAction(array $params): array
    {
        if ($params['request']->is(['get'])) {
            return [
                'data' => [
                    'title' => __('Fetch sharing group'),
                    'description' => __('Fetch and create/update sharing group ({0}) from MISP.', $params['uuid']),
                    'submit' => [
                        'action' => $params['request']->getParam('action')
                    ],
                    'url' => ['controller' => 'localTools', 'action' => 'action', $params['connection']['id'], 'fetchSharingGroupAction', $params['uuid']]
                ]
            ];
        } elseif ($params['request']->is(['post'])) {
            $response = $this->getData('/sharing_groups/view/' . $params['uuid'], $params);
            if ($response->getStatusCode() == 200) {
                $mispSG = $response->getJson();
                $sg = [
                    'uuid' => $mispSG['SharingGroup']['uuid'],
                    'name' => $mispSG['SharingGroup']['name'],
                    'releasability' => $mispSG['SharingGroup']['releasability'],
                    'description' => $mispSG['SharingGroup']['description'],
                    'organisation' => $mispSG['Organisation'],
                    'sharing_group_orgs' => []
                ];
                foreach ($mispSG['SharingGroupOrg'] as $sgo) {
                    $sg['sharing_group_orgs'][] = $sgo['Organisation'];
                }
                $result = $this->captureSharingGroup($sg, $params['user_id']);
                if ($result) {
                    return ['success' => 1, 'message' => __('Sharing group created/modified.')];
                } else {
                    return ['success' => 0, 'message' => __('Could not save the changes to the sharing group.')];
                }
            } else {
                return ['success' => 0, 'message' => __('Could not fetch the remote sharing group.')];
            }
        }
        throw new MethodNotAllowedException(__('Invalid http request type for the given action.'));
    }

    public function modifySettingAction(array $params): array
    {
        if ($params['request']->is(['get'])) {
            $response = $this->getData('/servers/getSetting/' . $params['setting'], $params);
            if ($response->getStatusCode() != 200) {
                throw new NotFoundException(__('Setting could not be fetched from the remote.'));
            }
            $response = $response->getJson();
            $types = [
                'string' => 'text',
                'boolean' => 'checkbox',
                'numeric' => 'number'
            ];
            $fields = [
                [
                    'field' => 'value',
                    'label' => __('Value'),
                    'default' => h($response['value']),
                    'type' => $types[$response['type']]
                ],
            ];
            return [
                'data' => [
                    'title' => __('Modify server setting'),
                    'description' => __('Modify setting ({0}) on connected MISP instance.', $params['setting']),
                    'fields' => $fields,
                    'submit' => [
                        'action' => $params['request']->getParam('action')
                    ],
                    'url' => ['controller' => 'localTools', 'action' => 'action', $params['connection']['id'], 'modifySettingAction', $params['setting']]
                ]
            ];
        } elseif ($params['request']->is(['post'])) {
            $params['body'] = ['value' => $params['value']];
            $response = $this->postData('/servers/serverSettingsEdit/' . $params['setting'], $params);
            if ($response->getStatusCode() == 200) {
                return ['success' => 1, 'message' => __('Setting saved.')];
            } else {
                return ['success' => 0, 'message' => __('Could not fetch the remote sharing group.')];
            }
        }
        throw new MethodNotAllowedException(__('Invalid http request type for the given action.'));

    }
}

 ?>
