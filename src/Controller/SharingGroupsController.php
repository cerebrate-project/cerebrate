<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;
use Cake\Error\Debugger;

class SharingGroupsController extends AppController
{
    public function index()
    {
        $this->CRUD->index([
            'contain' => ['SharingGroupOrgs', 'Organisations', 'Users' => ['fields' => ['id', 'username']]],
            'filters' => ['uuid', 'description', 'releasability', 'Organisations.name', 'Organisations.uuid']
        ]);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', 'Trust Circles');
    }

    public function add()
    {
        $this->CRUD->add([
            'override' => [
                'user_id' => $this->ACL->getUser()['id']
            ]
        ]);
        $dropdownData = [
            'organisation' => $this->getAvailableOrgForSg($this->ACL->getUser())
        ];
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set(compact('dropdownData'));
        $this->set('metaGroup', 'Trust Circles');
    }

    public function view($id)
    {
        $this->CRUD->view($id, [
            'contain' => ['SharingGroupOrgs', 'Organisations', 'Users' => ['fields' => ['id', 'username']]]
        ]);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', 'Trust Circles');
    }

    public function edit($id = false)
    {
        $this->CRUD->edit($id);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $dropdownData = [
            'organisation' => $this->getAvailableOrgForSg($this->ACL->getUser())
        ];
        $this->set(compact('dropdownData'));
        $this->set('metaGroup', 'Trust Circles');
        $this->render('add');
    }

    public function delete($id)
    {
        $this->CRUD->delete($id);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', 'Trust Circles');
    }

    public function addOrg($id)
    {
        $sharingGroup = $this->SharingGroups->get($id, [
            'contain' => 'SharingGroupOrgs'
        ]);
        $conditions = [];
        $containedOrgIds = array_values(\Cake\Utility\Hash::extract($sharingGroup, 'sharing_group_orgs.{n}.id'));
        if (!empty($containedOrgIds)) {
            $conditions = [
                'NOT' => [
                    'id IN' => $containedOrgIds
                ]
            ];
        }
        $dropdownData = [
            'organisation' => $this->SharingGroups->Organisations->find('list', [
                'sort' => ['name' => 'asc'],
                'conditions' => $conditions
            ])
        ];
        if ($this->request->is('post')) {
            $input = $this->request->getData();
            if (empty($input['organisation_id'])) {
                throw new InvalidArgumentException(__('No organisation IDs passed.'));
            }
            if (!is_array($input['organisation_id'])) {
                $input['organisation_id'] = [$input['organisation_id']];
            }
            $result = true;
            foreach ($input['organisation_id'] as $org_id) {
                $org = $this->SharingGroups->SharingGroupOrgs->get($org_id);
                $result &= (bool)$this->SharingGroups->SharingGroupOrgs->link($sharingGroup, [$org]);
            }
            if ($result) {
                $message = __('Organisation(s) added to the sharing group.');
            } else {
                $message = __('Organisation(s) could not be added to the sharing group.');
            }
            if ($this->ParamHandler->isRest()) {
                if ($result) {
                    $this->RestResponse->saveSuccessResponse('SharingGroups', 'addOrg', $id, 'json', $message);
                } else {
                    $this->RestResponse->saveFailResponse('SharingGroups', 'addOrg', $id, $message, 'json');
                }
            } else {
                if ($result) {
                    $this->Flash->success($message);
                } else {
                    $this->Flash->error($message);
                }
                $this->redirect(['action' => 'view', $id]);
            }
        }
        $this->set(compact('dropdownData'));
    }

    public function removeOrg($id)
    {

    }

    public function listOrgs($id)
    {
        $sharingGroup = $this->SharingGroups->get($id, [
            'contain' => 'SharingGroupOrgs'
        ]);
        $params = $this->ParamHandler->harvestParams(['quickFilter']);
        if (!empty($params['quickFilter'])) {
            foreach ($sharingGroup['sharing_group_orgs'] as $k => $org) {
                if (strpos($org['name'], $params['quickFilter']) === false) {
                    unset($sharingGroup['sharing_group_orgs'][$k]);
                }
            }
            $sharingGroup['sharing_group_orgs'] = array_values($sharingGroup['sharing_group_orgs']);
        }
        $this->set('sharing_group_id', $id);
        $this->set('sharing_group_orgs', $sharingGroup['sharing_group_orgs']);
    }

    private function getAvailableOrgForSg($user)
    {
        $organisations = [];
        if (!empty($user['role']['perm_admin'])) {
            $organisations = $this->SharingGroups->Organisations->find('list')->order(['name' => 'ASC'])->toArray();
        } else if (!empty($user['individual']['organisations'])) {
            $organisations = $this->SharingGroups->Organisations->find('list', [
                'sort' => ['name' => 'asc'],
                'conditions' => [
                    'id IN' => array_values(\Cake\Utility\Hash::extract($user, 'individual.organisations.{n}.id'))
                ]
            ]);
        }
        return $organisations;
    }
}
