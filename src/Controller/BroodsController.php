<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;
use Cake\ORM\TableRegistry;

class BroodsController extends AppController
{
    public $filterFields = ['Broods.name', 'Broods.uuid', 'Broods.url', 'Broods.description', 'Organisations.id', 'Broods.trusted', 'pull', 'authkey'];
    public $quickFilterFields = [['Broods.name' => true], 'Broods.uuid', ['Broods.description' => true]];
    public $containFields = ['Organisations'];

    public function index()
    {
        $this->CRUD->index([
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
            'contextFilters' => [
                'fields' => [
                    'pull',
                ]
            ],
            'contain' => $this->containFields
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'Sync');
    }

    public function add()
    {
        $this->CRUD->add();
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'Sync');
        $this->loadModel('Organisations');
        $dropdownData = [
            'organisation' => $this->Organisations->find('list', [
                'sort' => ['name' => 'asc']
            ])->toArray()
        ];
        $this->set(compact('dropdownData'));
    }

    public function view($id)
    {
        $this->CRUD->view($id, ['contain' => ['Organisations']]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'Sync');
    }

    public function edit($id)
    {
        $this->CRUD->edit($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'Sync');
        $this->loadModel('Organisations');
        $dropdownData = [
            'organisation' => $this->Organisations->find('list', [
                'sort' => ['name' => 'asc']
            ])->toArray()
        ];
        $this->set(compact('dropdownData'));
        $this->render('add');
    }

    public function delete($id)
    {
        $this->CRUD->delete($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'Sync');
    }

    public function testConnection($id)
    {
        $this->request->getSession()->close(); // close session to allow concurrent requests
        $status = $this->Broods->queryStatus($id);
        return $this->RestResponse->viewData($status, 'json');
    }

    public function previewIndex($id, $scope)
    {
        $validScopes = array_keys($this->Broods->previewScopes);
        if (!in_array($scope, $validScopes)) {
            throw new MethodNotAllowedException(__('Invalid scope. Valid options are: {0}', implode(', ', $validScopes)));
        }
        $filter = $this->request->getQuery('quickFilter');
        $data = $this->Broods->queryIndex($id, $scope, $filter, true);
        if (!is_array($data)) {
            $data = [];
        }
        if ($this->ParamHandler->isRest()) {
            return $this->RestResponse->viewData($data, 'json');
        } else {
            $data = $this->Broods->attachAllSyncStatus($data, $scope);
            $data = $this->CustomPagination->paginate($data);
            $optionFilters = ['quickFilter'];
            $CRUDParams = $this->ParamHandler->harvestParams($optionFilters);
            $CRUDOptions = [
                'quickFilters' => $this->Broods->previewScopes[$scope]['quickFilterFields'],
            ];
            $this->CRUD->setQuickFilterForView($CRUDParams, $CRUDOptions);
            $this->set('data', $data);
            $this->set('brood_id', $id);
            if ($this->request->is('ajax')) {
                $this->viewBuilder()->disableAutoLayout();
            }
            $this->set('metaGroup', 'Sync');
            $this->render('preview_' . $scope);
        }
    }

    public function downloadOrg($brood_id, $org_id)
    {
        if ($this->request->is('post')) {
            $result = $this->Broods->downloadOrg($brood_id, $org_id);
            $success = __('Organisation fetched from remote.');
            $fail = __('Could not save the remote organisation');
            if ($this->ParamHandler->isRest()) {
                if ($result) {
                    return $this->RestResponse->saveSuccessResponse('Brood', 'downloadOrg', $brood_id, 'json', $success);
                } else {
                    return $this->RestResponse->saveFailResponse('Brood', 'downloadOrg', $brood_id, $fail, 'json');
                }
            } else {
                if ($result) {
                    $this->Flash->success($success);
                } else {
                    $this->Flash->error($fail);
                }
                $this->redirect($this->referer());
            }
        }
        if ($org_id === 'all') {
            $question = __('All organisations from brood `{0}` will be downloaded. Continue?', h($brood_id));
            $title = __('Download all organisations from this brood');
            $actionName = __('Download all');
        } else {
            $question = __('The organisations `{0}` from brood `{1}` will be downloaded. Continue?', h($org_id), h($brood_id));
            $title = __('Download organisation from this brood');
            $actionName = __('Download organisation');
        }
        $this->set('title',  $title);
        $this->set('question', $question);
        $this->set('modalOptions', [
            'confirmButton' => [
                'variant' => $org_id === 'all' ? 'warning' : 'primary',
                'text' => $actionName,
            ],
        ]);
        $this->render('/genericTemplates/confirm');
    }

    public function downloadIndividual($brood_id, $individual_id)
    {
        $result = $this->Broods->downloadIndividual($brood_id, $individual_id);
        $success = __('Individual fetched from remote.');
        $fail = __('Could not save the remote individual');
        if ($this->ParamHandler->isRest()) {
            if ($result) {
                return $this->RestResponse->saveSuccessResponse('Brood', 'downloadIndividual', $brood_id, 'json', $success);
            } else {
                return $this->RestResponse->saveFailResponse('Brood', 'downloadIndividual', $brood_id, $fail, 'json');
            }
        } else {
            if ($result) {
                $this->Flash->success($success);
            } else {
                $this->Flash->error($fail);
            }
            $this->redirect($this->referer());
        }
    }

    public function downloadSharingGroup($brood_id, $sg_id)
    {
        $result = $this->Broods->downloadSharingGroup($brood_id, $sg_id, $this->ACL->getUser()['id']);
        $success = __('Sharing group fetched from remote.');
        $fail = __('Could not save the remote sharing group');
        if ($this->ParamHandler->isRest()) {
            if ($result) {
                return $this->RestResponse->saveSuccessResponse('Brood', 'downloadSharingGroup', $brood_id, 'json', $success);
            } else {
                return $this->RestResponse->saveFailResponse('Brood', 'downloadSharingGroup', $brood_id, $fail, 'json');
            }
        } else {
            if ($result) {
                $this->Flash->success($success);
            } else {
                $this->Flash->error($fail);
            }
            $this->redirect($this->referer());
        }
    }

    public function interconnectTools()
    {
        $this->InboxProcessors = TableRegistry::getTableLocator()->get('InboxProcessors');
        $processor = $this->InboxProcessors->getProcessor('Brood', 'ToolInterconnection');
        $data = [
            'origin' => '127.0.0.1',
            'comment' => 'Test comment',
            'user_id' => $this->ACL->getUser()->id,
            'data' => [
                'foo' => 'foo',
                'bar' => 'bar',
                'baz' => 'baz',
            ],
        ];
        $processorResult = $processor->create($data);
        return $processor->genHTTPReply($this, $processorResult, ['controller' => 'Broods', 'action' => 'index']);
    }
}
