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
    public function index()
    {
        $this->CRUD->index([
            'filters' => ['users.username', 'authkey', 'comment', 'users.id'],
            'quickFilters' => ['authkey', 'comment'],
            'contain' => ['Users'],
            'exclude_fields' => ['authkey']
        ]);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', $this->isAdmin ? 'Administration' : 'Cerebrate');
    }

    public function delete($id)
    {
        $this->CRUD->delete($id);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', $this->isAdmin ? 'Administration' : 'Cerebrate');
    }

    public function add()
    {
        $this->set('metaGroup', $this->isAdmin ? 'Administration' : 'Cerebrate');
        $this->CRUD->add([
            'displayOnSuccess' => 'authkey_display'
        ]);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->loadModel('Users');
        $dropdownData = [
            'user' => $this->Users->find('list', [
                'sort' => ['username' => 'asc']
            ])
        ];
        $this->set(compact('dropdownData'));
    }
}
