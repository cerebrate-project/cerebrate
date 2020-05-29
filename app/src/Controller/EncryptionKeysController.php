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
    public function index($owner_type = null, $owner_id = null)
    {
        $query = $this->EncryptionKeys->find();
        if (!empty($owner_type)) {
            $query->where(['owner_type' => $owner_type]);
        }
        if (!empty($owner_id)) {
            $query->where(['owner_id' => $owner_id]);
        }
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
            throw new NotFoundException(__('Invalid encrpyion keys.'));
        }
        $individual = $this->Alignments->get($id);
        if ($this->request->is('post') || $this->request->is('delete')) {
            if ($this->Alignments->delete($individual)) {
                $message = __('Individual deleted.');
                if ($this->_isRest()) {
                    $individual = $this->Alignments->get($id);
                    return $this->RestResponse->saveSuccessResponse('Alignments', 'delete', $id, 'json', $message);
                } else {
                    $this->Flash->success($message);
                    return $this->redirect($this->referer());
                }
            }
        }
        $this->set('metaGroup', 'ContactDB');
        $this->set('scope', 'alignments');
        $this->set('id', $individual['id']);
        $this->set('alignment', $individual);
        $this->viewBuilder()->setLayout('ajax');
        $this->render('/genericTemplates/delete');
    }

    public function add($owner_type = false, $owner_id = false)
    {
        if (empty($owner_type) && !empty($this->request->getData('owner_type'))) {
            $owner_type = $this->request->getData('owner_type');
        }
        if (empty($owner_id) && !empty($this->request->getData('owner_id'))) {
            $owner_id = $this->request->getData('owner_id');
        }
        if (empty($owner_type) || empty($owner_id)) {
            throw new NotAcceptableException(__('Invalid input. owner_type and owner_id expected as parameters in the format /encryption_keys/add/[owner_type]/[owner_id] or passed as a JSON.'));
        }
        if ($owner_type === 'individual') {
            $this->loadModel('Individuals');
            $owner = $this->Individuals->find()->where(['id' => $owner_id])->first();
            if (empty($owner)) {
                throw new NotFoundException(__('Invalid owner individual.'));
            }
        } else {
            $this->loadModel('Organisations');
            $owner = $this->Organisations->find()->where(['id' => $owner_id])->first();
            if (empty($owner)) {
                throw new NotFoundException(__('Invalid owner individual.'));
            }
        }
        $encryptionKey = $this->EncryptionKeys->newEmptyEntity();
        if ($this->request->is('post')) {
            $this->EncryptionKeys->patchEntity($encryptionKey, $this->request->getData());
            $encrypionKey['owner_type'] = $owner_type;
            if ($this->EncryptionKeys->save($encryptionKey)) {
                $message = __('EncryptionKey added.');
                if ($this->_isRest()) {
                    $encryptionKey = $this->EncryptionKeys->get($this->EncryptionKeys->id);
                    return $this->RestResponse->viewData($encryptionKey, 'json');
                } else {
                    $this->Flash->success($message);
                    $this->redirect($this->referer());
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

        $this->set(compact('owner'));
        $this->set(compact('encryptionKey'));
        $this->set(compact('owner_id'));
        $this->set(compact('owner_type'));
        $this->set('metaGroup', 'ContactDB');
    }
}
