<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Inflector;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;
use Cake\Error\Debugger;

class SharingGroupsController extends AppController
{
    public $filterFields = ['SharingGroups.uuid', 'SharingGroups.name', 'description', 'releasability', 'Organisations.name', 'Organisations.uuid'];
    public $quickFilterFields = ['SharingGroups.uuid', ['SharingGroups.name' => true], ['description' => true], ['releasability' => true]];
    public $containFields = ['SharingGroupOrgs', 'Organisations', 'Users' => ['fields' => ['id', 'username']]];

    public function index()
    {
        $currentUser = $this->ACL->getUser();
        $conditions = [];
        if (empty($currentUser['role']['perm_admin'])) {
            $conditions['SharingGroups.organisation_id'] = $currentUser['organisation_id'];
        }
        $this->CRUD->index([
            'contain' => $this->containFields,
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
            'conditions' => $conditions
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
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
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set(compact('dropdownData'));
        $this->set('metaGroup', 'Trust Circles');
    }

    public function view($id)
    {
        $this->CRUD->view($id, [
            'contain' => ['SharingGroupOrgs', 'Organisations', 'Users' => ['fields' => ['id', 'username']]]
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'Trust Circles');
    }

    public function edit($id = false)
    {
        $params = [];
        $currentUser = $this->ACL->getUser();
        if (empty($currentUser['role']['perm_admin'])) {
            $params['conditions'] = ['organisation_id' => $currentUser['organisation_id']];
        }
        $params['fields'] = ['name', 'releasability', 'description', 'active'];
        $this->CRUD->edit($id, $params);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
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
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
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
            if ($this->ParamHandler->isRest() || $this->ParamHandler->isAjax()) {
                if ($result) {
                    $savedData = $this->SharingGroups->get($id, [
                        'contain' => 'SharingGroupOrgs'
                    ]);
                    return $this->RestResponse->ajaxSuccessResponse(Inflector::singularize($this->SharingGroups->getAlias()), 'addOrg', $savedData, $message);
                } else {
                    return $this->RestResponse->ajaxFailResponse(Inflector::singularize($this->SharingGroups->getAlias()), 'addOrg', $sharingGroup, $message);;
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

    public function removeOrg($id, $org_id)
    {
        $sharingGroup = $this->SharingGroups->get($id, [
            'contain' => 'SharingGroupOrgs'
        ]);
        if ($this->request->is('post')) {
            $org = $this->SharingGroups->SharingGroupOrgs->get($org_id);
            $result = (bool)$this->SharingGroups->SharingGroupOrgs->unlink($sharingGroup, [$org]);
            if ($result) {
                $message = __('Organisation(s) removed from the sharing group.');
            } else {
                $message = __('Organisation(s) could not be removed to the sharing group.');
            }
            if ($this->ParamHandler->isRest() || $this->ParamHandler->isAjax()) {
                if ($result) {
                    $savedData = $this->SharingGroups->get($id, [
                        'contain' => 'SharingGroupOrgs'
                    ]);
                    return $this->RestResponse->ajaxSuccessResponse(Inflector::singularize($this->SharingGroups->getAlias()), 'removeOrg', $savedData, $message);
                } else {
                    return $this->RestResponse->ajaxFailResponse(Inflector::singularize($this->SharingGroups->getAlias()), 'removeOrg', $sharingGroup, $message);
                    ;
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
        $this->set('scope', 'sharing_groups');
        $this->set('id', $org_id);
        $this->set('sharingGroup', $sharingGroup);
        $this->set('deletionText', __('Are you sure you want to remove Organisation #{0} from Sharing group #{1}?', $org_id, $sharingGroup['id']));
        $this->set('postLinkParameters', ['action' => 'removeOrg', $id, $org_id]);
        $this->viewBuilder()->setLayout('ajax');
        $this->render('/genericTemplates/delete');
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
        } else {
            $organisations = $this->SharingGroups->Organisations->find('list', [
                'sort' => ['name' => 'asc'],
                'conditions' => [
                    'id' => $user['organisation_id']
                ]
            ]);
        }
        return $organisations;
    }
}
