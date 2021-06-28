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

    public function genHTTPClient(Object $brood, array $options=[]): Object
    {
        $defaultOptions = [
            'headers' => [
                'Authorization' => $brood->authkey,
            ],
        ];
        if (empty($options['type'])) {
            $options['type'] = 'json';
        }
        $options = array_merge($defaultOptions, $options);
        $http = new Client($options);
        return $http;
    }

    public function HTTPClientGET(String $relativeURL, Object $brood, array $data=[], array $options=[]): Object
    {
        $http = $this->genHTTPClient($brood, $options);
        $url = sprintf('%s%s', $brood->url, $relativeURL);
        return $http->get($url, $data, $options);
    }

    public function HTTPClientPOST(String $relativeURL, Object $brood, $data, array $options=[]): Object
    {
        $http = $this->genHTTPClient($brood, $options);
        $url = sprintf('%s%s', $brood->url, $relativeURL);
        return $http->post($url, $data, $options);
    }

    public function queryStatus($id)
    {
        $brood = $this->find()->where(['id' => $id])->first();
        $start = microtime(true);
        $response = $this->HTTPClientGET('/instance/status.json', $brood);
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
        $filterQuery = empty($filter) ? '' : '?quickFilter=' . urlencode($filter);
        $response = $this->HTTPClientGET(sprintf('/%s/index.json%s', $scope, $filterQuery), $brood);
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
        $response = $this->HTTPClientGET(sprintf('/%s/view/%s/index.json', $scope, $org_id), $brood);
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
        $response = $this->HTTPClientGET(sprintf('/organisations/view/%s/index.json', $org_id), $brood);
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
        $response = $this->HTTPClientGET(sprintf('/individuals/view/%s/index.json', $individual_id), $brood);
        if ($response->isOk()) {
            $individual = $response->getJson();
            $this->Individuals = TableRegistry::getTableLocator()->get('Individuals');
            $result = $this->Individuals->captureIndividual($individual);
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
        $response = $this->HTTPClientGET('/localTools/exposedTools', $brood);
        if ($response->isOk()) {
            return $response->getJson();
        } else {
            return false;
        }
    }

    public function sendRequest($brood, $urlPath, $methodPost = true, $data = []): Response
    {
        if ($methodPost) {
            $response = $this->HTTPClientPOST($urlPath, $brood, json_encode($data));
        } else {
            $response = $this->HTTPClientGET($urlPath, $brood, $data);
        }
        return $response;
    }

    private function injectRequiredData($params, $data): Array
    {
        $data['connectorName'] = $params['remote_tool']['connector'];
        $data['cerebrateURL'] = Configure::read('App.fullBaseUrl');
        $data['local_tool_id'] = $params['connection']['id'];
        $data['remote_tool_id'] = $params['remote_tool']['id'];
        $data['tool_name'] = $params['connection']['name'];
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
                $jsonReply = $this->handleMessageNotCreated($params['remote_cerebrate'], $url, $data, 'LocalTool', 'IncomingConnectionRequest', $response, $params, 'STATE_INITIAL');
            }
        } catch (NotFoundException $e) {
            $jsonReply = $this->handleSendingFailed($params['remote_cerebrate'], $url, $data, 'LocalTool', 'IncomingConnectionRequest', $e, $params, 'STATE_INITIAL');
        }
        return $jsonReply;
    }

    public function sendLocalToolAcceptedRequest($params, $data): array
    {
        $url = '/inbox/createEntry/LocalTool/AcceptedRequest';
        $data = $this->injectRequiredData($params, $data);
        try {
            $response = $this->sendRequest($params['remote_cerebrate'], $url, true, $data);
            $jsonReply = $response->getJson();
            if (empty($jsonReply['success'])) {
                $jsonReply = $this->handleMessageNotCreated($params['remote_cerebrate'], $url, $data, 'LocalTool', 'AcceptedRequest', $response, $params, 'STATE_CONNECTED');
            } else {
                $this->setRemoteToolConnectionStatus($params, 'STATE_CONNECTED');
            }
        } catch (NotFoundException $e) {
            $jsonReply = $this->handleSendingFailed($params['remote_cerebrate'], $url, $data, 'LocalTool', 'AcceptedRequest', $e, $params, 'STATE_CONNECTED');
        }
        return $jsonReply;
    }

    public function sendLocalToolDeclinedRequest($params, $data): array
    {
        $url = '/inbox/createEntry/LocalTool/DeclinedRequest';
        $data = $this->injectRequiredData($params, $data);
        try {
            $response = $this->sendRequest($params['remote_cerebrate'], $url, true, $data);
            $jsonReply = $response->getJson();
            if (empty($jsonReply['success'])) {
                $jsonReply = $this->handleMessageNotCreated($params['remote_cerebrate'], $url, $data, 'LocalTool', 'AcceptedRequest', $response, $params, 'STATE_DECLINED');
            }
        } catch (NotFoundException $e) {
            $jsonReply = $this->handleSendingFailed($params['remote_cerebrate'], $url, $data, 'LocalTool', 'AcceptedRequest', $e, $params, 'STATE_DECLINED');
        }
        return $jsonReply;
    }
    
    /**
     * handleSendingFailed - Handle the case if the request could not be sent or if the remote rejected the connection request
     *
     * @param  Object $response
     * @return array
     */
    private function handleSendingFailed($brood, $url, $data, $model, $action, $e, $params, $next_connector_state): array
    {
        $connector = $params['connector'][$params['remote_tool']['connector']];
        $reason = [
            'message' => __('Failed to send message to remote cerebrate. It has been placed in the outbox.'),
            'errors' => [$e->getMessage()],
        ];
        $outboxSaveResult = $this->saveErrorInOutbox($brood, $url, $data, $reasonMessage, $params, $next_connector_state);
        $connector->remoteToolConnectionStatus($params, $connector::STATE_SENDING_ERROR);
        $creationResult = [
            'success' => false,
            'message' => $reason['message'],
            'errors' => $reason['errors'],
            'placed_in_outbox' => !empty($outboxSaveResult['success']),
        ];
        return $creationResult;
    }
    
    /**
     * handleMessageNotCreated - Handle the case if the request was sent but the remote brood did not save the message in the inbox
     *
     * @param  Object $response
     * @return array
     */
    private function handleMessageNotCreated($brood, $url, $data, $model, $action, $response, $params, $next_connector_state): array
    {
        $connector = $params['connector'][$params['remote_tool']['connector']];
        $responseErrors = $response->getStringBody();
        if (!is_null($response->getJson())) {
            $responseErrors = $response->getJson()['errors'] ?? $response->getJson()['message'];
        }
        $reason = [
            'message' => __('Message rejected by the remote cerebrate. It has been placed in the outbox.'),
            'errors' => [$responseErrors],
        ];
        $outboxSaveResult = $this->saveErrorInOutbox($brood, $url, $data, $reason, $model, $action, $params, $next_connector_state);
        $connector->remoteToolConnectionStatus($params, $connector::STATE_SENDING_ERROR);
        $creationResult = [
            'success' => false,
            'message' => $reason['message'],
            'errors' => $reason['errors'],
            'placed_in_outbox' => !empty($outboxSaveResult['success']),
        ];
        return $creationResult;
    }

    private function saveErrorInOutbox($brood, $url, $data, $reason, $model, $action, $params, $next_connector_state): array
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
                'next_connector_state' => $next_connector_state,
            ],
            'brood' => $brood,
            'model' => $model,
            'action' => $action,
        ];
        $creationResult = $processor->create($entryData);
        return $creationResult;
    }

    private function setRemoteToolConnectionStatus($params, String $status): void
    {
        $connector = $params['connector'][$params['remote_tool']['connector']];
        $connector->remoteToolConnectionStatus($params, constant(get_class($connector) . '::' . $status));
    }
}
