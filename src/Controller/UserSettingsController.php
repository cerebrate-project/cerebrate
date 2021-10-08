<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;

class UserSettingsController extends AppController
{
    public $quickFilterFields = [['name' => true], ['value' => true]];
    public $filterFields = ['name', 'value', 'Users.id'];
    public $containFields = ['Users'];

    public function index()
    {
        $conditions = [];
        $this->CRUD->index([
            'conditions' => [],
            'contain' => $this->containFields,
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        if (!empty($this->request->getQuery('Users_id'))) {
            $settingsForUser = $this->UserSettings->Users->find()->where([
                'id' => $this->request->getQuery('Users_id')
            ])->first();
            $this->set('settingsForUser', $settingsForUser);
        }
    }

    public function add($user_id = false)
    {
        $this->CRUD->add([
            'redirect' => ['action' => 'index', $user_id],
            'beforeSave' => function($data) use ($user_id) {
                $data['user_id'] = $user_id;
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $dropdownData = [
            'user' => $this->UserSettings->Users->find('list', [
                'sort' => ['username' => 'asc']
            ]),
        ];
        $this->set(compact('dropdownData'));
        $this->set('user_id', $user_id);
    }

    public function edit($id)
    {
        $entity = $this->UserSettings->find()->where([
            'id' => $id
        ])->first();
        $entity = $this->CRUD->edit($id, [
            'redirect' => ['action' => 'index', $entity->user_id]
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $dropdownData = [
            'user' => $this->UserSettings->Users->find('list', [
                'sort' => ['username' => 'asc']
            ]),
        ];
        $this->set(compact('dropdownData'));
        $this->set('user_id', $this->entity->user_id);
        $this->render('add');
    }

    public function delete($id)
    {
        $this->CRUD->delete($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

}