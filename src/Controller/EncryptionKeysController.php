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
    public $filterFields = ['owner_model', 'organisation_id', 'individual_id', 'encryption_key'];
    public $quickFilterFields = ['encryption_key'];
    public $containFields = ['Individuals', 'Organisations'];
    public $statisticsFields = ['type'];

    public function index()
    {
        $this->CRUD->index([
            'quickFilters' => $this->quickFilterFields,
            'filters' => $this->filterFields,
            'contextFilters' => [
                'fields' => [
                    'type'
                ]
            ],
            'contain' => $this->containFields,
            'statisticsFields' => $this->statisticsFields,
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
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

    public function add()
    {
        $this->CRUD->add(['redirect' => $this->referer()]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
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
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('dropdownData', []);
        $this->set('metaGroup', 'ContactDB');
        $this->render('add');
    }
}
