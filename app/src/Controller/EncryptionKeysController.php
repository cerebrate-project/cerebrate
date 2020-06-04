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
        $params = $this->_harvestParams($this->request, ['owner_type', 'owner_id']);
        $query = $this->EncryptionKeys->find();
        if (!empty($params['owner_type'])) {
            $query->where(['owner_type' => $params['owner_type']]);
        }
        if (!empty($params['owner_id'])) {
            $query->where(['owner_id' => $params['owner_id']]);
        }
        $query->contain(['Individuals', 'Organisations']);
        if ($this->_isRest()) {
            $alignments = $query->all();
            return $this->RestResponse->viewData($alignments, 'json');
        } else {
            $this->loadComponent('Paginator');
            $encrpyion_keys = $this->Paginator->paginate($query);
            $this->set('data', $encrpyion_keys);
            $this->set('metaGroup', 'ContactDB');
        }
    }

    public function delete($id)
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid encryption key.'));
        }
        $key = $this->EncryptionKeys->get($id);
        if ($this->request->is('post') || $this->request->is('delete')) {
            if ($this->EncryptionKey->delete($individual)) {
                $message = __('Encryption key deleted.');
                if ($this->_isRest()) {
                    $individual = $this->EncryptionKeys->get($id);
                    return $this->RestResponse->saveSuccessResponse('EncryptionKeys', 'delete', $id, 'json', $message);
                } else {
                    $this->Flash->success($message);
                    return $this->redirect($this->referer());
                }
            }
        }
        $this->set('metaGroup', 'ContactDB');
        $this->set('scope', 'encryptionKeys');
        $this->set('id', $key['id']);
        $this->set('key', $key);
        $this->viewBuilder()->setLayout('ajax');
        $this->render('/genericTemplates/delete');
    }

    public function add()
    {
        $params = $this->_harvestParams($this->request, ['owner_type', 'owner_id', 'organisation_id', 'individual_id', 'encryption_key', 'expires', 'uuid', 'revoked', 'type']);
        $input = $this->request->getData();
        $encryptionKey = $this->EncryptionKeys->newEmptyEntity();
        if (!empty($params['owner_type'])) {
            if (!empty($params[$params['owner_type'] . '_id'])) {
                $params['owner_id'] = $params[$params['owner_type'] . '_id'];
            }
            $params[$params['owner_type'] . '_id'] = $params['owner_id'];
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
        if ($this->request->is('post')) {
            if (empty($params['owner_type']) || empty($params['owner_id'])) {
                throw new NotAcceptableException(__('Invalid input. owner_type and owner_id expected as parameters in the format /encryption_keys/add/[owner_type]/[owner_id] or passed as a JSON.'));
            }
            if ($params['owner_type'] === 'individual') {
                $owner = $this->Individuals->find()->where(['id' => $params['owner_id']])->first();
                if (empty($owner)) {
                    throw new NotFoundException(__('Invalid owner individual.'));
                }
            } else {
                $owner = $this->Organisations->find()->where(['id' => $params['owner_id']])->first();
                if (empty($owner)) {
                    throw new NotFoundException(__('Invalid owner organisation.'));
                }
            }
            $encryptionKey = $this->EncryptionKeys->patchEntity($encryptionKey, $params);
            if ($this->EncryptionKeys->save($encryptionKey)) {
                $message = __('EncryptionKey added.');
                if ($this->_isRest()) {
                    $encryptionKey = $this->EncryptionKeys->get($this->EncryptionKeys->id);
                    return $this->RestResponse->viewData($encryptionKey, 'json');
                } else {
                    $this->Flash->success($message);
                    return $this->redirect(['action' => 'index']);
                }
            } else {
                $message = __('EncryptionKey could not be added.');
                if ($this->_isRest()) {
                    return $this->RestResponse->saveFailResponse('EncryptionKeys', 'add', false, $message);
                } else {
                    $this->Flash->error($message);
                    $this->redirect($this->referer());
                }
            }
        }
        $this->set('encryptionKey', $encryptionKey);
        $this->set(compact('dropdownData'));
        $this->set('metaGroup', 'ContactDB');
    }
}
