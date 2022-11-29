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

    protected $previewScopes = [
        'organisations' => [
            'quickFilterFields' => ['uuid', ['name' => true], ],
        ],
        'individuals' => [
            'quickFilterFields' => ['uuid', ['email' => true], ['first_name' => true], ['last_name' => true], ],
        ],
        'sharingGroups' => [
            'quickFilterFields' => ['uuid', ['name' => true], ],
        ],
    ];

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
            ])
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
            ])
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
        $status = $this->Broods->queryStatus($id);
        return $this->RestResponse->viewData($status, 'json');
    }

    public function previewIndex($id, $scope)
    {
        $validScopes = array_keys($this->previewScopes);
        if (!in_array($scope, $validScopes)) {
            throw new MethodNotAllowedException(__('Invalid scope. Valid options are: {0}', implode(', ', $validScopes)));
        }
        $filter = $this->request->getQuery('quickFilter');
        $data = $this->Broods->queryIndex($id, $scope, $filter);
        if (!is_array($data)) {
            $data = [];
        }
        if ($this->ParamHandler->isRest()) {
            return $this->RestResponse->viewData($data, 'json');
        } else {
            $data = $this->CustomPagination->paginate($data);
            $optionFilters = ['quickFilter'];
            $CRUDParams = $this->ParamHandler->harvestParams($optionFilters);
            $CRUDOptions = [
                'quickFilters' => $this->previewScopes[$scope]['quickFilterFields'],
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
