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
            'contain' => $this->containFields
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
    }

    public function delete($id)
    {
        $orgConditions = [];
        $individualConditions = [];
        $dropdownData = [];
        $currentUser = $this->ACL->getUser();
        $params = [];
        if (empty($currentUser['role']['perm_admin'])) {
            $params = $this->buildBeforeSave($params, $currentUser, $orgConditions, $individualConditions, $dropdownData);
        }
        $this->CRUD->delete($id, $params);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
    }

    private function buildBeforeSave(array $params, $currentUser, array &$orgConditions, array &$individualConditions, array &$dropdownData): array
    {
        $orgConditions = [
            'id' => $currentUser['organisation_id']
        ];
        if (empty($currentUser['role']['perm_org_admin'])) {
            $individualConditions = [
                'id' => $currentUser['individual_id']
            ];
        }
        $params['beforeSave'] = function($entity) use($currentUser) {
            if ($entity['owner_model'] === 'organisation') {
                $entity['owner_id'] = $currentUser['organisation_id'];
            } else {
                if ($currentUser['role']['perm_org_admin']) {
                    $this->loadModel('Alignments');
                    $validIndividuals = $this->Alignments->find('list', [
                        'keyField' => 'individual_id',
                        'valueField' => 'id',
                        'conditions' => ['organisation_id' => $currentUser['organisation_id']]
                    ])->toArray();
                    if (!isset($validIndividuals[$entity['owner_id']])) {
                        throw new MethodNotAllowedException(__('Selected individual cannot be linked by the current user.'));
                    }
                } else {
                    if ($entity['owner_id'] !== $currentUser['id']) {
                        throw new MethodNotAllowedException(__('Selected individual cannot be linked by the current user.'));
                    }
                }
            }
            return $entity;
        };
        $this->loadModel('Organisations');
        $this->loadModel('Individuals');
        $dropdownData = [
            'organisation' => $this->Organisations->find('list')->order(['name' => 'asc'])->where($orgConditions)->all()->toArray(),
            'individual' => $this->Individuals->find('list')->order(['email' => 'asc'])->where($individualConditions)->all()->toArray()
        ];
        return $params;
    }

    public function add()
    {
        $orgConditions = [];
        $individualConditions = [];
        $dropdownData = [];
        $currentUser = $this->ACL->getUser();
        $params = [
            'redirect' => $this->referer()
        ];
        if (empty($currentUser['role']['perm_admin'])) {
            $params = $this->buildBeforeSave($params, $currentUser, $orgConditions, $individualConditions, $dropdownData);
        }
        $this->CRUD->add($params);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set(compact('dropdownData'));
        $this->set('metaGroup', 'ContactDB');
    }

    public function edit($id = false)
    {
        $orgConditions = [];
        $individualConditions = [];
        $dropdownData = [];
        $currentUser = $this->ACL->getUser();
        $params = [
            'fields' => [
                'type', 'encryption_key', 'revoked'
            ],
            'redirect' => $this->referer()
        ];
        if (empty($currentUser['role']['perm_admin'])) {
            $params = $this->buildBeforeSave($params, $currentUser, $orgConditions, $individualConditions, $dropdownData);
        }
        $this->CRUD->edit($id, $params);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('dropdownData', []);
        $this->set('metaGroup', 'ContactDB');
        $this->render('add');
    }

    public function view($id = false)
    {
        $this->CRUD->view($id, [
            'contain' => ['Individuals', 'Organisations']
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
    }
}
