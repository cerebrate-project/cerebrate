<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;

class LocalToolsController extends AppController
{
    public function index()
    {
        $data = $this->LocalTools->extractMeta($this->LocalTools->getConnectors(), true);
        if ($this->ParamHandler->isRest()) {
            return $this->RestResponse->viewData($data, 'json');
        }
        $data = $this->CustomPagination->paginate($data);
        $this->set('data', $data);
        if ($this->request->is('ajax')) {
            $this->viewBuilder()->disableAutoLayout();
        }
        $this->set('metaGroup', 'Administration');
    }

    public function connectorIndex($connectorName)
    {
        $this->set('metaGroup', 'Admin');
        $this->CRUD->index([
            'filters' => ['name', 'connector'],
            'quickFilters' => ['name', 'connector'],
            'filterFunction' => function($query) use ($connectorName) {
                $query->where(['connector' => $connectorName]);
                return $query;
            },
            'afterFind' => function($data) {
                foreach ($data as $connector) {
                    $connector['health'] = [$this->LocalTools->healthCheckIndividual($connector)];
                }
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $connector = $this->LocalTools->getConnectors($connectorName)[$connectorName];
        $this->set('metaGroup', 'Administration');
        $this->set('connectorName', $connectorName);
        $this->set('connector', $connector);
    }

    public function batchAction($actionName)
    {
        $params = $this->ParamHandler->harvestParams(['connection_ids']);
        $params['connection_ids'] = explode(',', $params['connection_ids']);
        $connections = $this->LocalTools->query()->where(['id IN' => $params['connection_ids']])->all();
        if (empty($connections)) {
            throw new NotFoundException(__('Invalid connector.'));
        }
        $connection = $connections->first();
        if ($this->request->is(['post', 'put'])) {
            $actionParams = $this->LocalTools->getActionFilterOptions($connection->connector, $actionName);
            $params = array_merge($params, $this->ParamHandler->harvestParams($actionParams));
            $results = [];
            $successes = 0;
            $this->LocalTools->loadConnector($connection->connector);
            foreach ($connections as $connection) {
                $actionDetails = $this->LocalTools->getActionDetails($actionName);
                $params['connection'] = $connection;
                try {
                    $tmpResult = $this->LocalTools->action($this->ACL->getUser()['id'], $connection->connector, $actionName, $params, $this->request);
                } catch (\Exception $e) {
                    $tmpResult = ['success' => false, 'message' => $e->getMessage(), 'data' => []];
                }
                $tmpResult['connection'] = $connection;
                $results[$connection->id] = $tmpResult;
                $successes += $tmpResult['success'] ? 1 : 0;
            }
            $success = $successes > 0;
            $message = __('{0} / {1} operations were successful', $successes, count($results));
            $this->CRUD->setResponseForController('batchAction', $success, $message, $results, $results);
            $responsePayload = $this->CRUD->getResponsePayload();
            if (!empty($responsePayload)) {
                return $responsePayload;
            }
            if (!empty($success)) {
                $this->Flash->success($message);
                $this->redirect(['controller' => 'localTools', 'action' => 'connectorIndex', $actionName]);
            } else {
                $this->Flash->error($message);
                $this->redirect(['controller' => 'localTools', 'action' => 'connectorIndex', $actionName]);
            }
        } else {
            $params['connection'] = $connection;
            $results = $this->LocalTools->action($this->ACL->getUser()['id'], $connection->connector, $actionName, $params, $this->request);
            $this->set('data', $results);
            $this->set('metaGroup', 'Administration');
            $this->render('/Common/getForm');
        }
    }

    public function action($connectionId, $actionName)
    {
        $connection = $this->LocalTools->query()->where(['id' => $connectionId])->first();
        if (empty($connection)) {
            throw new NotFoundException(__('Invalid connector.'));
        }
        $params = $this->ParamHandler->harvestParams($this->LocalTools->getActionFilterOptions($connection->connector, $actionName));
        $actionDetails = $this->LocalTools->getActionDetails($actionName);
        $params['connection'] = $connection;
        $results = $this->LocalTools->action($this->ACL->getUser()['id'], $connection->connector, $actionName, $params, $this->request);
        if (!empty($results['redirect'])) {
            $this->redirect($results['redirect']);
        }
        if (!empty($results['restResponse'])) {
            return $results['restResponse'];
        }
        if ($this->ParamHandler->isRest()) {
            return $results['data']['data'];
        }
        $this->set('data', $results);
        $this->set('metaGroup', 'Administration');
        if ($actionDetails['type'] === 'formAction') {
            if ($this->request->is(['post', 'put'])) {
                if ($this->ParamHandler->isAjax()) {
                    if (!empty($results['success'])) {
                        return $this->RestResponse->ajaxSuccessResponse(
                            'LocalTools',
                            'action',
                            $connection,
                            empty($results['message']) ? __('Success.') : $results['message']
                        );
                    } else {
                        return $this->RestResponse->ajaxSuccessResponse(
                            'LocalTools',
                            'action',
                            false,
                            empty($results['message']) ? __('Success.') : $results['message']
                            //['displayOnSuccess' => $displayOnSuccess]
                        );
                    }
                } else {
                    if (!empty($results['success'])) {
                        $this->Flash->success(empty($results['message']) ? __('Success.') : $results['message']);
                        $this->redirect(['controller' => 'localTools', 'action' => 'action', $connectionId, $actionDetails['redirect']]);
                    } else {
                        $this->Flash->error(empty($results['message']) ? __('Could not execute the requested action.') : $results['message']);
                        $this->redirect(['controller' => 'localTools', 'action' => 'action', $connectionId, $actionDetails['redirect']]);
                    }
                }
            } else {
                $this->render('/Common/getForm');
            }
        } else {
            $this->render('/Common/' . $actionDetails['type']);
        }
    }

    public function add($connectorName = false)
    {
        $this->CRUD->add();
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $localConnectors = $this->LocalTools->extractMeta($this->LocalTools->getConnectors());
        $dropdownData = ['connectors' => []];
        $connector = false;
        $connectors = [];
        foreach ($localConnectors as $c) {
            if (empty($connectorName) || $c['connector'] == $connectorName) {
                $dropdownData['connectors'][$c['connector']] = $c['name'];
                $connectors[] = $c;
            }
        }
        $this->set('connectorName', $connectorName);
        $this->set('connectors', $connectors);
        $this->set(compact('dropdownData'));
        $this->set('metaGroup', 'Administration');
    }

    public function viewConnector($connector_name)
    {
        $connectors = $this->LocalTools->extractMeta($this->LocalTools->getConnectors());
        $connector = false;
        foreach ($connectors as $c) {
            if ($connector_name == $c['connector'] && ($connector === false || version_compare($c['version'], $connectors['version']) > 0)) {
                $connector = $c;
            }
        }
        if ($this->ParamHandler->isRest()) {
            return $this->RestResponse->viewData($connector, 'json');
        }
        $this->set('entity', $connector);
        $this->set('metaGroup', 'Administration');
    }

    public function edit($id)
    {
        $localTool = $this->LocalTools->get($id);
        $this->CRUD->edit($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $localConnectors = $this->LocalTools->extractMeta($this->LocalTools->getConnectors());
        $dropdownData = ['connectors' => []];
        $connector = false;
        $connectors = [];
        foreach ($localConnectors as $c) {
            if (empty($localTool->connector) || $c['connector'] == $localTool->connector) {
                $dropdownData['connectors'][$c['connector']] = $c['name'];
                $connectors[] = $c;
            }
        }
        $this->set(compact('dropdownData'));
        $this->set('connectorName', $localTool->connector);
        $this->set('connectors', $connectors);
        $this->set('metaGroup', 'Administration');
        $this->render('add');
    }

    public function delete($id)
    {
        $this->CRUD->delete($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'Administration');
    }

    public function view($id)
    {
        $localTools = $this->LocalTools;
        $this->CRUD->view($id, [
            'afterFind' => function ($data) use($id, $localTools) {
                $data['children'] = $localTools->getChildParameters($id);
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'Administration');
    }

    public function exposedTools()
    {
        $this->CRUD->index([
            'filters' => ['name', 'connector'],
            'quickFilters' => ['name', 'connector'],
            'fields' => ['id', 'name', 'connector', 'description'],
            'filterFunction' => function($query) {
                $query->where(['exposed' => 1]);
                return $query;
            },
            'afterFind' => function($data) {
                foreach ($data as $connector) {
                    $connectorById = $this->LocalTools->getConnectorByConnectionId($connector['id']);
                    $className = array_keys($connectorById)[0];
                    $connector['connectorName'] = $className;
                }
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'Administration');
    }

    public function broodTools($id)
    {
        $this->loadModel('Broods');
        $tools = $this->Broods->queryLocalTools($id);
        foreach ($tools as $k => $tool) {
            $tools[$k]['local_tools'] = $this->LocalTools->appendLocalToolConnections($id, $tool);
        }
        if ($this->ParamHandler->isRest()) {
            return $this->RestResponse->viewData($tools, 'json');
        }
        $this->set('id', $id);
        $brood = $this->Broods->get($id);
        $this->set('broodEntity', $brood);
        $this->set('data', $tools);
        $this->set('metaGroup', 'Administration');
    }

    public function connectionRequest($cerebrate_id, $remote_tool_id)
    {
        $params = [
            'cerebrate_id' => $cerebrate_id,
            'remote_tool_id' => $remote_tool_id
        ];
        $this->loadModel('Broods');
        $remoteCerebrate = $this->Broods->find()->where(['id' => $params['cerebrate_id']])->first();
        if ($this->request->is(['post', 'put'])) {
            $postParams = $this->ParamHandler->harvestParams(['local_tool_id']);
            if (empty($postParams['local_tool_id'])) {
                throw new MethodNotAllowedException(__('No local tool ID supplied.'));
            }
            $params['local_tool_id'] = $postParams['local_tool_id'];
            try {
                $encodingResult = $this->LocalTools->encodeConnection($params);
            } catch (\Exception $e) {
                $encodingResult = [
                    'inboxResult' => [
                        'success' => false,
                        'message' => __('Error while trying to encode connection'),
                        'errors' => [$e->getMessage()],
                    ],
                ];
            }
            $inboxResult = $encodingResult['inboxResult'];
            if ($inboxResult['success']) {
                if ($this->ParamHandler->isRest()) {
                    $response = $this->RestResponse->viewData($inboxResult, 'json');
                } else if ($this->ParamHandler->isAjax()) {
                    $response = $this->RestResponse->ajaxSuccessResponse('LocalTool', 'connectionRequest', [], $inboxResult['message']);
                } else {
                    $this->Flash->success($inboxResult['message']);
                    $response = $this->redirect(['action' => 'broodTools', $cerebrate_id]);
                }
            } else {
                if ($this->ParamHandler->isRest()) {
                    $response = $this->RestResponse->viewData($inboxResult, 'json');
                } else if ($this->ParamHandler->isAjax()) {
                    $response = $this->RestResponse->ajaxFailResponse('LocalTool', 'connectionRequest', [], $inboxResult['message'], $inboxResult['errors']);
                } else {
                    $this->Flash->error($inboxResult['message']);
                    $response = $this->redirect($this->referer());
                }
            }
            return $response;
        } else {
            $remoteTool = $this->LocalTools->getRemoteToolById($params);
            $local_tools = $this->LocalTools->encodeConnectionChoice($params);
            if (empty($local_tools)) {
                throw new NotFoundException(__('No local equivalent tool found.'));
            }
            $this->set('data', [
                'remoteCerebrate' => $remoteCerebrate,
                'remoteTool' => $remoteTool,
                'local_tools' => $local_tools
            ]);
        }
    }

/*
    public function connectLocal($local_tool_id)
    {
        $params = [
            'local_tool_id' => $local_tool_id
        ];
        $local_tool = $this->LocalTools->fetchConnection($local_tool_id);
        if ($this->request->is(['post', 'put'])) {
            $postParams = $this->ParamHandler->harvestParams(['target_tool_id']);
            if (empty($postParams['target_tool_id'])) {
                throw new MethodNotAllowedException(__('No target tool ID supplied.'));
            }

            $params['target_tool_id'] = $postParams['target_tool_id'];
            $result = $this->LocalTools->encodeLocalConnection($params);
            // Send message to remote inbox
        } else {
            $target_tools = $this->LocalTools->findConnectable($local_tool);
            if (empty($target_tools)) {
                throw new NotFoundException(__('No tools found to connect.'));
            }
            $this->set('data', [
                'remoteCerebrate' => $remoteCerebrate,
                'remoteTool' => $remoteTool,
                'local_tools' => $local_tools
            ]);
        }
    }
*/
}
