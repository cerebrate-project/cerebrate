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

class EncryptionKeysController extends AppController
{
    public function index()
    {
        $this->CRUD->index([
            'quickFilters' => ['encryption_key'],
            'filters' => ['owner_type', 'organisation_id', 'individual_id', 'encryption_key'],
            'contain' => ['Individuals', 'Organisations']
        ]);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
    }

    public function delete($id)
    {
        $this->CRUD->delete($id);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
    }

    public function add()
    {
        $this->CRUD->add(['redirect' => $this->referer()]);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->loadModel('Organisations');
        $this->loadModel('Individuals');
        $dropdownData = [
            'organisation' => $this->Organisations->find('list', [
                'sort' => ['name' => 'asc']
            ]),
            'individual' => $this->Individuals->find('list', [
                'sort' => ['email' => 'asc']
            ])
        ];
        $this->set(compact('dropdownData'));
        $this->set('metaGroup', 'ContactDB');
    }

    public function edit($id = false)
    {
        $params = [
            'fields' => [
                'type', 'encryption_key', 'revoked'
            ],
            'redirect' => $this->referer()
        ];
        $this->CRUD->edit($id, $params);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('dropdownData', []);
        $this->set('metaGroup', 'ContactDB');
        $this->render('add');
    }
}
