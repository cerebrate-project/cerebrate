<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;

class UsersController extends AppController
{
    public function index()
    {
        $this->CRUD->index([
            'contain' => ['Individuals', 'Roles'],
            'filters' => ['Users.email', 'uuid']
        ]);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', $this->isAdmin ? 'Administration' : 'Cerebrate');
    }

    public function add()
    {
        $this->CRUD->add();
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $dropdownData = [
            'role' => $this->Users->Roles->find('list', [
                'sort' => ['name' => 'asc']
            ]),
            'individual' => $this->Users->Individuals->find('list', [
                'sort' => ['email' => 'asc']
            ])
        ];
        $this->set(compact('dropdownData'));
        $this->set('metaGroup', $this->isAdmin ? 'Administration' : 'Cerebrate');
    }

    public function view($id = false)
    {
        if (empty($id) || empty($this->ACL->getUser()['role']['perm_admin'])) {
            $id = $this->ACL->getUser()['id'];
        }
        $this->CRUD->view($id, [
            'contain' => ['Individuals' => ['Alignments' => 'Organisations'], 'Roles']
        ]);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', $this->isAdmin ? 'Administration' : 'Cerebrate');
    }

    public function edit($id = false)
    {
        if (empty($id) || empty($this->ACL->getUser()['role']['perm_admin'])) {
            $id = $this->ACL->getUser()['id'];
        }
        $params = [
            'get' => [
                'fields' => [
                    'id', 'individual_id', 'role_id', 'username', 'disabled'
                ]
            ],
            'removeEmpty' => [
                'password'
            ],
            'fields' => [
                'id', 'individual_id', 'username', 'disabled', 'password', 'confirm_password'
            ]
        ];
        if (!empty($this->ACL->getUser()['role']['perm_admin'])) {
            $params['fields'][] = 'role_id';
        }
        $this->CRUD->edit($id, $params);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $dropdownData = [
            'role' => $this->Users->Roles->find('list', [
                'sort' => ['name' => 'asc']
            ]),
            'individual' => $this->Users->Individuals->find('list', [
                'sort' => ['email' => 'asc']
            ])
        ];
        $this->set(compact('dropdownData'));
        $this->set('metaGroup', $this->isAdmin ? 'Administration' : 'Cerebrate');
        $this->render('add');
    }

    public function delete($id)
    {
        $this->CRUD->delete($id);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', $this->isAdmin ? 'Administration' : 'Cerebrate');
    }

    public function login()
    {
        $result = $this->Authentication->getResult();
        // If the user is logged in send them away.
        if ($result->isValid()) {
            $target = $this->Authentication->getLoginRedirect() ?? '/instance/home';
            return $this->redirect($target);
        }
        if ($this->request->is('post') && !$result->isValid()) {
            $this->Flash->error(__('Invalid username or password'));
        }
        $this->viewBuilder()->setLayout('login');
    }

    public function logout()
    {
        $result = $this->Authentication->getResult();
        if ($result->isValid()) {
            $this->Authentication->logout();
            $this->Flash->success(__('Goodbye.'));
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }
    }
}
