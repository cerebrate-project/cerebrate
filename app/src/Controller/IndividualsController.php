<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;

class IndividualsController extends AppController
{
    public function index()
    {
        $query = $this->Individuals->find();
        $filterFields = ['uuid', 'email', 'first_name', 'last_name', 'position'];
        if (!empty($this->request->getQuery('quickFilter'))) {
            $quickFilter = $this->request->getQuery('quickFilter');
            $conditions = [];
            foreach ($filterFields as $field) {
                $conditions[] = [$field . ' LIKE' => '%' . $quickFilter . '%'];
            }
        }
        $quickFilter = $this->request->getQuery('quickFilter');
        foreach ($filterFields as $filterField) {
            $tempFilter = $this->request->getQuery($filterField);
            if (!empty($tempFilter)) {
                if (strpos($tempFilter, '%') !== false) {
                    $conditions[] = [$filterField . ' LIKE' => $tempFilter];
                } else {
                    $conditions[] = [$filterField => $tempFilter];
                }
            }
        }
        $query->contain(['Alignments' => 'Organisations']);
        if (!empty($this->request->getQuery('organisation_id'))) {
            $query->matching('Alignments', function($q) {
                return $q->where(['Alignments.organisation_id' => $this->request->getQuery('organisation_id')]);
            });
        }
        if (!empty($conditions)) {
            $query->where($conditions);
        }
        if ($this->_isRest()) {
            $individuals = $query->all();
            return $this->RestResponse->viewData($individuals, 'json');
        } else {
            $this->loadComponent('Paginator');
            $individuals = $this->Paginator->paginate($query);
            $this->set('data', $individuals);
            $this->set('alignmentScope', 'individuals');
            $this->set('metaGroup', 'ContactDB');
        }
    }

    public function add()
    {
        $individual = $this->Individuals->newEmptyEntity();
        if ($this->request->is('post')) {
            $individual = $this->Individuals->patchEntity($individual, $this->request->getData());
            if ($this->Individuals->save($individual)) {
                $message = __('Individual added.');
                if ($this->_isRest()) {
                    $individual = $this->Individuals->get($id);
                    return $this->RestResponse->viewData($individual, 'json');
                } else {
                    $this->Flash->success($message);
                    $this->redirect(['action' => 'index']);
                }
            } else {
                $message = __('Individual could not be added.');
                if ($this->_isRest()) {

                } else {
                    $this->Flash->error($message);
                }
            }
        }
        $this->set('metaGroup', 'ContactDB');
        $this->set('individual', $individual);
    }

    public function view($id)
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid organisation.'));
        }
        $individual = $this->Individuals->get($id, [
            'contain' => ['Alignments' => 'Organisations']
        ]);
        if ($this->_isRest()) {
            return $this->RestResponse->viewData($individual, 'json');
        } else {

        }
        $this->set('metaGroup', 'ContactDB');
        $this->set('individual', $individual);
    }

    public function edit($id)
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid organisation.'));
        }
        $individual = $this->Individuals->get($id);
        if ($this->request->is(['post', 'put'])) {
            $this->Individuals->patchEntity($individual, $this->request->getData());
            if ($this->Individuals->save($individual)) {
                $message = __('Individual updated.');
                if ($this->_isRest()) {
                    $individual = $this->Individuals->get($id);
                    return $this->RestResponse->viewData($individual, 'json');
                } else {
                    $this->Flash->success($message);
                    return $this->redirect(['action' => 'index']);
                }
            } else {
                if ($this->_isRest()) {

                }
            }
        }
        $this->set('metaGroup', 'ContactDB');
        $this->set('individual', $individual);
        $this->render('add');
    }

    public function delete($id)
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid organisation.'));
        }
        $individual = $this->Individuals->get($id);
        if ($this->request->is('post') || $this->request->is('delete')) {
            if ($this->Individuals->delete($individual)) {
                $message = __('Individual deleted.');
                if ($this->_isRest()) {
                    $individual = $this->Individuals->get($id);
                    return $this->RestResponse->saveSuccessResponse('Individuals', 'delete', $id, 'json', $message);
                } else {
                    $this->Flash->success($message);
                    return $this->redirect($this->referer());
                }
            }
        }
        $this->set('metaGroup', 'ContactDB');
        $this->set('scope', 'individuals');
        $this->set('id', $individual['id']);
        $this->set('individual', $individual);
        $this->viewBuilder()->setLayout('ajax');
        $this->render('/genericTemplates/delete');
    }
}
