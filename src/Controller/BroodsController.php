<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;

class BroodsController extends AppController
{
    public function index()
    {
        $this->CRUD->index([
            'filters' => ['name', 'uuid', 'url', 'description', 'Organisations.id', 'trusted', 'pull', 'authkey'],
            'quickFilters' => ['name', 'uuid', 'description'],
            'contain' => ['Organisations']
        ]);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', 'Sync');
    }

    public function add()
    {
        $this->CRUD->add();
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
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
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', 'Sync');
    }

    public function edit($id)
    {
        $this->CRUD->edit($id);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', 'Sync');
        $this->render('add');
    }

    public function delete($id)
    {
        $this->CRUD->delete($id);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
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
        if (!in_array($scope, ['organisations', 'individuals', 'sharingGroups'])) {
            throw new MethodNotAllowedException(__('Invalid scope. Valid options are: organisations, individuals, sharing_groups'));
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
}
