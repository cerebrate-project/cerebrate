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
    public $filterFields = ['owner_model', 'owner_id', 'encryption_key'];
    public $quickFilterFields = ['encryption_key'];
    public $containFields = ['Individuals', 'Organisations'];
    public $statisticsFields = ['type'];

    public function index()
    {
        $this->EncryptionKeys->initializeGpg();
        $Model = $this->EncryptionKeys;
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
            'afterFind' => function($data) use ($Model) {
                if ($data['type'] === 'pgp') {
                    $keyInfo = $Model->verifySingleGPG($data);
                    $data['status'] = __('OK');
                    $data['fingerprint'] = __('N/A');
                    if (!$keyInfo[0]) {
                        $data['status'] = $keyInfo[2];
                    }
                    if (!empty($keyInfo[4])) {
                        $data['fingerprint'] = $keyInfo[4];
                    }
                }
                return $data;
            }
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
        if (empty($currentUser['role']['perm_admin'])) {
            $orgConditions = [
                'id' => $currentUser['organisation_id']
            ];
            if (empty($currentUser['role']['perm_org_admin'])) {
                $individualConditions = [
                    'id' => $currentUser['individual_id']
                ];
            } else {
                $this->loadModel('Alignments');
                $individualConditions = ['id IN' => $this->Alignments->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'individual_id',
                    'conditions' => ['organisation_id' => $currentUser['organisation_id']]
                ])->toArray()];
            }
            $params['beforeSave'] = function($entity) use($currentUser) {
                if ($entity['owner_model'] === 'organisation') {
                    if ($entity['owner_id'] !== $currentUser['organisation_id']) {
                        throw new MethodNotAllowedException(__('Selected organisation cannot be linked by the current user.'));
                    }
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
        }
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
        $params = $this->buildBeforeSave($params, $currentUser, $orgConditions, $individualConditions, $dropdownData);
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
        $this->EncryptionKeys->initializeGpg();
        $Model = $this->EncryptionKeys;
        $this->CRUD->view($id, [
            'contain' => ['Individuals', 'Organisations'],
            'afterFind' => function($data) use ($Model) {
                if ($data['type'] === 'pgp') {
                    $keyInfo = $Model->verifySingleGPG($data);
                    if (!$keyInfo[0]) {
                        $data['pgp_error'] = $keyInfo[2];
                    }
                    if (!empty($keyInfo[4])) {
                        $data['pgp_fingerprint'] = $keyInfo[4];
                    }
                }
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
    }
}
