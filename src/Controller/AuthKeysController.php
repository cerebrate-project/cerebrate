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
        $userId = $this->request->getQuery('Users_id');
        if (!empty($userId)) {
            $conditions['AND']['Users.id'] = $userId;
        }

        if (empty($currentUser['role']['perm_community_admin'])) {
            $conditions['Users.organisation_id'] = $currentUser['organisation_id'];
            if (empty($currentUser['role']['perm_org_admin'])) {
                $conditions['Users.id'] = $currentUser['id'];
            }
        }
        $indexOptions = [
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
            'contain' => $this->containFields,
            'exclude_fields' => ['authkey'],
            'conditions' => $conditions,
            'hidden' => []
        ];
        if (!empty($userId)) {
            $indexOptions['action_query_strings'] = ['Users.id' => $userId];
        }
        $this->CRUD->index($indexOptions);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', $this->isCommunityAdmin ? 'Administration' : 'Cerebrate');
    }

    public function delete($id)
    {
        $currentUser = $this->ACL->getUser();
        $conditions = $this->AuthKeys->buildUserConditions($currentUser);
        $this->CRUD->delete($id, ['conditions' => $conditions, 'contain' => 'Users']);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', $this->isCommunityAdmin ? 'Administration' : 'Cerebrate');
    }

    public function add()
    {
        $this->set('metaGroup', $this->isCommunityAdmin ? 'Administration' : 'Cerebrate');
        $validUsers = [];
        $userConditions = [];
        $currentUser = $this->ACL->getUser();
        $conditions = $this->AuthKeys->buildUserConditions($currentUser);
        $userId = $this->request->getQuery('Users_id');
        $users = $this->Users->find('list');
        if (!empty($conditions)) {
            $users->where($conditions);
        }
        if (!empty($userId)) {
            $users->where(['Users.id' => $userId]);
        }
        $users = $users->order(['username' => 'asc'])->all()->toArray();
        $this->CRUD->add([
            'displayOnSuccess' => 'authkey_display',
            'beforeSave' => function($data) use ($users, $currentUser, $userId) {
                $data['user_id'] = $userId ?? $data['user_id'];
                if (empty($currentUser['role']['perm_community_admin']) && !in_array($data['user_id'], array_keys($users))) {
                    throw new MethodNotAllowedException(__('You are not authorised to do that.'));
                }
                if (empty($data['expiration'])) {
                    $data['expiration'] = 0;
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
        $this->entity->user_id = $currentUser['id'];
        $this->set(compact('dropdownData'));
    }
}
