<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Error\Debugger;

class BroodsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->BelongsTo(
            'Organisations'
        );
        $this->setDisplayField('name');
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator;
    }

    public function queryStatus($id)
    {
        $brood = $this->find()->where(['id' => $id])->first();
        $http = new Client();
        $start = microtime(true);
        $response = $http->get($brood['url'] . '/instance/status.json', [], [
            'headers' => [
                'Authorization' => $brood['authkey'],
                'Accept' => 'Application/json',
                'Content-type' => 'Application/json'
            ]
        ]);
        $ping = ((int)(100 * (microtime(true) - $start)));
        $errors = [
            403 => [
                'error' => __('Authentication failure'),
                'reason' => __('Invalid user credentials.')
            ],
            404 => [
                'error' => __('Not found'),
                'reason' => __('Incorrect URL or proxy error')
            ],
            405 => [
                'error' => __('Insufficient privileges'),
                'reason' => __('The remote user account doesn\'t have the required privileges to synchronise.')
            ],
            500 => [
                'error' => __('Internal error'),
                'reason' => __('Something is probably broken on the remote side. Get in touch with the instance owner.')
            ]
        ];
        $result = [
            'code' => $response->getStatusCode()
        ];
        if ($response->isOk()) {
            $raw = $response->getJson();
            $result['response']['role'] = $raw['user']['role'];
            $result['response']['user'] = $raw['user']['username'];
            $result['response']['application'] = $raw['application'];
            $result['response']['version'] = $raw['version'];
            $result['ping'] = $ping;
        } else {
            $result['error'] = $errors[$result['code']]['error'];
            $result['reason'] = $errors[$result['code']]['reason'];
            $result['ping'] = $ping;
        }
        return $result;
    }

    public function queryIndex($id, $scope, $filter)
    {
        $brood = $this->find()->where(['id' => $id])->first();
        if (empty($brood)) {
            throw new NotFoundException(__('Brood not found'));
        }
        $http = new Client();
        $filterQuery = empty($filter) ? '' : '?quickFilter=' . urlencode($filter);
        $response = $http->get($brood['url'] . '/' . $scope . '/index.json' . $filterQuery , [], [
            'headers' => [
                'Authorization' => $brood['authkey'],
                'Accept' => 'Application/json',
                'Content-type' => 'Application/json'
            ]
        ]);
        if ($response->isOk()) {
            return $response->getJson();
        } else {
            return false;
        }
    }

    public function downloadAndCapture($brood_id, $object_id, $scope, $path)
    {
        $query = $this->find();
        $brood = $query->where(['id' => $brood_id])->first();
        if (empty($brood)) {
            throw new NotFoundException(__('Brood not found'));
        }
        $http = new Client();
        $response = $http->get($brood['url'] . '/' . $scope . '/view/' . $org_id . '/index.json' , [], [
            'headers' => [
                'Authorization' => $brood['authkey'],
                'Accept' => 'Application/json',
                'Content-type' => 'Application/json'
            ]
        ]);
        if ($response->isOk()) {
            $org = $response->getJson();
            $this->Organisation = TableRegistry::getTableLocator()->get('Organisations');
            $result = $this->Organisation->captureOrg($org);
            return $result;
        } else {
            return false;
        }
    }

    public function downloadOrg($brood_id, $org_id)
    {
        $query = $this->find();
        $brood = $query->where(['id' => $brood_id])->first();
        if (empty($brood)) {
            throw new NotFoundException(__('Brood not found'));
        }
        $http = new Client();
        $response = $http->get($brood['url'] . '/organisations/view/' . $org_id . '/index.json' , [], [
            'headers' => [
                'Authorization' => $brood['authkey'],
                'Accept' => 'Application/json',
                'Content-type' => 'Application/json'
            ]
        ]);
        if ($response->isOk()) {
            $org = $response->getJson();
            $this->Organisation = TableRegistry::getTableLocator()->get('Organisations');
            $result = $this->Organisation->captureOrg($org);
            return $result;
        } else {
            return false;
        }
    }

    public function downloadIndividual($brood_id, $individual_id)
    {
        $query = $this->find();
        $brood = $query->where(['id' => $brood_id])->first();
        if (empty($brood)) {
            throw new NotFoundException(__('Brood not found'));
        }
        $http = new Client();
        $response = $http->get($brood['url'] . '/individuals/view/' . $individual_id . '/index.json' , [], [
            'headers' => [
                'Authorization' => $brood['authkey'],
                'Accept' => 'Application/json',
                'Content-type' => 'Application/json'
            ]
        ]);
        if ($response->isOk()) {
            $org = $response->getJson();
            $this->Individual = TableRegistry::getTableLocator()->get('Individual');
            $result = $this->Individual->captureIndividual($individual);
            return $result;
        } else {
            return false;
        }
    }

    public function queryLocalTools($brood_id)
    {
        $query = $this->find();
        $brood = $query->where(['id' => $brood_id])->first();
        if (empty($brood)) {
            throw new NotFoundException(__('Brood not found'));
        }
        $http = new Client();
        $response = $http->get($brood['url'] . '/localTools/exposedTools' , [], [
            'headers' => [
                'Authorization' => $brood['authkey']
            ],
            'type' => 'json'
        ]);
        if ($response->isOk()) {
            return $response->getJson();
        } else {
            return false;
        }
    }

    public function sendRequest($brood, $urlPath, $methodPost = true, $data = []): Response
    {
        $http = new Client();
        $config = [
            'headers' => [
                'AUTHORIZATION' => $brood->authkey,
                'Accept' => 'application/json'
            ],
            'type' => 'json'
        ];
        $url = $brood->url . $urlPath;
        if ($methodPost) {
            $response = $http->post($url, json_encode($data), $config);
        } else {
            $response = $http->get($brood->url, $data, $config);
        }
        return $response;
    }

    private function injectRequiredData($params, $data): Array
    {
        $data['connectorName'] = $params['remote_tool']['connector'];
        $data['cerebrateURL'] = Configure::read('App.fullBaseUrl');
        $data['local_tool_id'] = $params['connection']['id'];
        $data['remote_tool_id'] = $params['remote_tool']['id'];
        return $data;
    }

    public function sendLocalToolConnectionRequest($params, $data): array
    {
        $url = '/inbox/createEntry/LocalTool/IncomingConnectionRequest';
        $data = $this->injectRequiredData($params, $data);
        try {
            $response = $this->sendRequest($params['remote_cerebrate'], $url, true, $data);
            $jsonReply = $response->getJson();
            if (empty($jsonReply['success'])) {
                $jsonReply = $this->handleMessageNotCreated($params['remote_cerebrate'], $url, $data, 'LocalTool', 'IncomingConnectionRequest', $response, $params);
            }
        } catch (NotFoundException $e) {
            $jsonReply = $this->handleSendingFailed($params['remote_cerebrate'], $url, $data, 'LocalTool', 'IncomingConnectionRequest', $e, $params);
        }
        return $jsonReply;
    }

    public function sendLocalToolAcceptedRequest($params, $data): Response
    {
        $url = '/inbox/createEntry/LocalTool/AcceptedRequest';
        $data = $this->injectRequiredData($params, $data);
        return $this->sendRequest($params['remote_cerebrate'], $url, true, $data);
    }

    public function sendLocalToolDeclinedRequest($params, $data): Response
    {
        $url = '/inbox/createEntry/LocalTool/DeclinedRequest';
        $data = $this->injectRequiredData($params, $data);
        return $this->sendRequest($params['remote_cerebrate'], $url, true, $data);
    }
    
    /**
     * handleSendingFailed - Handle the case if the request could not be sent or if the remote rejected the connection request
     *
     * @param  Object $response
     * @return array
     */
    private function handleSendingFailed($brood, $url, $data, $model, $action, $e, $params): array
    {
        $reason = [
            'message' => __('Failed to send message to remote cerebrate. It has been placed in the outbox.'),
            'errors' => [$e->getMessage()],
        ];
        $this->saveErrorInOutbox($brood, $url, $data, $reasonMessage, $params);
        $creationResult = [
            'success' => false,
            'message' => $reason['message'],
            'errors' => $reason['errors'],
        ];
        return $creationResult;
    }
    
    /**
     * handleMessageNotCreated - Handle the case if the request was sent but the remote brood did not save the message in the inbox
     *
     * @param  Object $response
     * @return array
     */
    private function handleMessageNotCreated($brood, $url, $data, $model, $action, $response, $params): array
    {
        $responseErrors = $response->getStringBody();
        if (!is_null($response->getJson())) {
            $responseErrors = $response->getJson()['errors'] ?? $response->getJson()['message'];
        }
        $reason = [
            'message' => __('Message rejected by the remote cerebrate. It has been placed in the outbox.'),
            'errors' => [$responseErrors],
        ];
        $this->saveErrorInOutbox($brood, $url, $data, $reason, $model, $action, $params);
        $creationResult = [
            'success' => false,
            'message' => $reason['message'],
            'errors' => $reason['errors'],
        ];
        return $creationResult;
    }

    private function saveErrorInOutbox($brood, $url, $data, $reason, $model, $action, $params): array
    {
        $this->OutboxProcessors = TableRegistry::getTableLocator()->get('OutboxProcessors');
        $processor = $this->OutboxProcessors->getProcessor('Broods', 'ResendFailedMessage');
        $entryData = [
            'data' => [
                'sent' => $data,
                'url' => $url,
                'brood_id' => $brood->id,
                'reason' => $reason,
                'local_tool_id' => $params['connection']['id'],
                'remote_tool' => $params['remote_tool'],
            ],
            'brood' => $brood,
            'model' => $model,
            'action' => $action,
        ];
        $creationResult = $processor->create($entryData);
        return $creationResult;
    }
}
