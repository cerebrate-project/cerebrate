<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotAcceptableException;
use Cake\Error\Debugger;

class AuthKeysController extends AppController
{
    public $filterFields = ['Users.username', 'authkey', 'comment', 'Users.id'];
    public $quickFilterFields = ['authkey', ['comment' => true]];
    public $containFields = ['Users' => ['fields' => ['id', 'username']]];

    public function index()
    {
        $currentUser = $this->ACL->getUser();
        $conditions = [];
        if (empty($currentUser['role']['perm_admin'])) {
            $conditions['Users.organisation_id'] = $currentUser['organisation_id'];
            if (empty($currentUser['role']['perm_org_admin'])) {
                $conditions['Users.id'] = $currentUser['id'];
            }
        }
        $this->CRUD->index([
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
            'contain' => $this->containFields,
            'exclude_fields' => ['authkey'],
            'conditions' => $conditions,
            'hidden' => []
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', $this->isAdmin ? 'Administration' : 'Cerebrate');
    }

    public function delete($id)
    {
        $currentUser = $this->ACL->getUser();
        $conditions = [];
        if (empty($currentUser['role']['perm_admin'])) {
            $conditions['Users.organisation_id'] = $currentUser['organisation_id'];
            if (empty($currentUser['role']['perm_org_admin'])) {
                $conditions['Users.id'] = $currentUser['id'];
            }
        }
        $this->CRUD->delete($id, ['conditions' => $conditions, 'contain' => 'Users']);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', $this->isAdmin ? 'Administration' : 'Cerebrate');
    }

    public function add()
    {
        $this->set('metaGroup', $this->isAdmin ? 'Administration' : 'Cerebrate');
        $validUsers = [];
        $userConditions = [];
        $currentUser = $this->ACL->getUser();
        if (empty($currentUser['role']['perm_admin'])) {
            if (empty($currentUser['role']['perm_org_admin'])) {
                $userConditions['id'] = $currentUser['id'];
            } else {
                $role_ids = $this->Users->Roles->find()->where(['perm_admin' => 0])->all()->extract('id')->toList();
                $userConditions['role_id IN'] = $role_ids;
            }
        }
        $users = $this->Users->find('list');
        if (!empty($userConditions)) {
            $users->where($userConditions);
        }
        $users = $users->order(['username' => 'asc'])->all()->toArray();
        $this->CRUD->add([
            'displayOnSuccess' => 'authkey_display',
            'beforeSave' => function($data) use ($users) {
                if (!in_array($data['user_id'], array_keys($users))) {
                    throw new MethodNotAllowedException(__('You are not authorised to do that.'));
                }
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload([
            'displayOnSuccess' => 'authkey_display'
        ]);
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->loadModel('Users');
        $dropdownData = [
            'user' => $users
        ];
        $this->set(compact('dropdownData'));
    }
}
