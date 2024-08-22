<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;

class OrgGroupsController extends AppController
{

    public $quickFilterFields = [['name' => true], 'uuid'];
    public $filterFields = ['name', 'uuid'];
    public $containFields = ['Organisations'];
    public $statisticsFields = [];

    public function index()
    {
        $additionalContainFields = [];

        $this->CRUD->index([
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
            'quickFilterForMetaField' => ['enabled' => true, 'wildcard_search' => true],
            'contain' => $this->containFields,
            'statisticsFields' => $this->statisticsFields,
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
    }

    public function filtering()
    {
        $this->CRUD->filtering();
    }

    public function add()
    {
        $this->CRUD->add();
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function view($id)
    {
        $this->CRUD->view($id, ['contain' => ['Organisations']]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('canEdit', $this->canEdit($id));
        $this->set('canEditDefinition', $this->canEditDefinition($id));
    }

    public function edit($id)
    {
        if (!$this->canEdit($id)) {
            throw new MethodNotAllowedException(__('You cannot modify that group.'));
        }
        $this->CRUD->edit($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
        $this->render('add');
    }

    public function delete($id)
    {
        $this->CRUD->delete($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
    }

    public function tag($id)
    {
        if (!$this->canEdit($id)) {
            throw new MethodNotAllowedException(__('You cannot tag that organisation.'));
        }
        $this->CRUD->tag($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function untag($id)
    {
        if (!$this->canEdit($id)) {
            throw new MethodNotAllowedException(__('You cannot untag that organisation.'));
        }
        $this->CRUD->untag($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function viewTags($id)
    {
        $this->CRUD->viewTags($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    private function canEdit($groupId): bool
    {
        $currentUser = $this->ACL->getUser();
        if ($currentUser['role']['perm_community_admin']) {
            return true;
        }
        if ($currentUser['role']['perm_group_admin']) {
            $orgGroup = $this->OrgGroups->get($groupId, ['contain' => 'Users']);
            $found = false;
            foreach ($orgGroup['users'] as $admin) {
                if ($admin['id'] == $currentUser['id']) {
                    $found = true;
                }
            }
            return $found;
        }
        return false;
    }

    private function canEditDefinition($groupId): bool
    {
        $currentUser = $this->ACL->getUser();
        if ($currentUser['role']['perm_community_admin']) {
            return true;
        }
        return false;
    }

    // Listing should be available to all, it's purely informational
    public function listAdmins($groupId)
    {
        if (empty($groupId)) {
            throw new NotFoundException(__('Invalid {0}.', 'OrgGroup'));
        }
        $orgGroup = $this->OrgGroups->get($groupId, ['contain' => ['Users' => ['Individuals', 'Organisations']]]);
        $this->set('data', $orgGroup['users']);
        $this->set('canEdit', $this->ACL->getUser()['role']['perm_community_admin']);
        $this->set('groupId', $groupId);
    }

    // Listing should be available to all, it's purely informational
    public function listOrgs($groupId)
    {
        if (empty($groupId)) {
            throw new NotFoundException(__('Invalid {0}.', 'OrgGroup'));
        }
        $orgGroup = $this->OrgGroups->get($groupId, ['contain' => 'Organisations']);
        $this->set('data', $orgGroup['organisations']);
        $this->set('canEdit', $this->canEdit($groupId));
        $this->set('groupId', $groupId);
    }

    public function assignAdmin($groupId)
    {
        if (!$this->ACL->getUser()['role']['perm_community_admin']) {
            throw new MethodNotAllowedException(__('You do not have permission to edit this group.'));
        }
        $this->CRUD->linkObjects(__FUNCTION__, $groupId, 'OrgGroups', 'Users', ['redirect' => '/orgGroups/listAdmins/' . $groupId]);
        if ($this->request->is('post')) {
            $responsePayload = $this->CRUD->getResponsePayload();
            if (!empty($responsePayload)) {
                return $responsePayload;
            }
        }
        $orgGroup = $this->OrgGroups->get($groupId, ['contain' => 'Users']);
        $this->loadModel('Users');
        $this->loadModel('Roles');
        $validRoles = $this->Roles->find('list')->disableHydration()->select(
            ['id', 'name']
        )->where(
            ['OR' => ['perm_community_admin' => 1, 'perm_group_admin' => 1]]
        )->toArray();
        $admins = $this->Users->find('list')->disableHydration()->select(['id', 'username'])->where(['Users.role_id IN' => array_keys($validRoles)])->toArray();
        asort($admins, SORT_STRING | SORT_FLAG_CASE);
        if (!empty($orgGroup['users'])) {
            foreach ($orgGroup['users'] as $admin) {
                if (isset($admins[$admin['id']])) {
                    unset($admins[$admin['id']]);
                }
            }
        }
        $dropdownData = [
            'admins' => $admins
        ];
        $this->set(compact('dropdownData'));
    }

    public function removeAdmin($groupId, $adminId)
    {
        if (!$this->ACL->getUser()['role']['perm_community_admin']) {
            throw new MethodNotAllowedException(__('You do not have permission to edit this group.'));
        }
        $this->CRUD->unlinkObjects(__FUNCTION__, $groupId, $adminId, 'OrgGroups', 'Users');
        if ($this->request->is('post')) {
            $responsePayload = $this->CRUD->getResponsePayload();
            if (!empty($responsePayload)) {
                return $responsePayload;
            }
        }
        $this->viewBuilder()->setLayout('ajax');
        $this->render('/genericTemplates/detach');
    }

    public function attachOrg($groupId)
    {
        if (!$this->OrgGroups->checkIfGroupAdmin($groupId, $this->ACL->getUser())) {
            throw new MethodNotAllowedException(__('You do not have permission to edit this group.'));
        }
        $this->CRUD->linkObjects(__FUNCTION__, $groupId, 'OrgGroups', 'Organisations', ['redirect' => '/orgGroups/listOrgs/' . $groupId]);
        if ($this->request->is('post')) {
            $responsePayload = $this->CRUD->getResponsePayload();
            if (!empty($responsePayload)) {
                return $responsePayload;
            }
        }
        $orgGroup = $this->OrgGroups->get($groupId, ['contain' => 'Organisations']);
        $this->loadModel('Organisations');
        $orgs = $this->Organisations->find('list')->disableHydration()->select(['id', 'name'])->toArray();
        asort($orgs, SORT_STRING | SORT_FLAG_CASE);
        foreach ($orgGroup['organisations'] as $organisation) {
            if (isset($orgs[$organisation['id']])) {
                unset($orgs[$organisation['id']]);
            }
        }
        $dropdownData = [
            'orgs' => $orgs
        ];
        $this->set(compact('dropdownData'));
    }

    public function detachOrg($groupId, $orgId)
    {
        if (!$this->OrgGroups->checkIfGroupAdmin($groupId, $this->ACL->getUser())) {
            throw new MethodNotAllowedException(__('You do not have permission to edit this group.'));
        }
        $this->CRUD->unlinkObjects(__FUNCTION__, $groupId, $orgId, 'OrgGroups', 'Organisations');
        if ($this->request->is('post')) {
            $responsePayload = $this->CRUD->getResponsePayload();
            if (!empty($responsePayload)) {
                return $responsePayload;
            }
        }
        $this->viewBuilder()->setLayout('ajax');
        $this->render('/genericTemplates/detach');
    }
}
