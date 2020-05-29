<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;

class OrganisationsController extends AppController
{
    public function index()
    {
        $filterFields = ['name', 'uuid', 'nationality', 'sector', 'type', 'url'];
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
        $query = $this->Organisations->find();
        $query->contain(['Alignments' => 'Individuals']);
        if (!empty($this->request->getQuery('individual_id'))) {
            $query->matching('Alignments', function($q) {
                return $q->where(['Alignments.individual_id' => $this->request->getQuery('individual_id')]);
            });
        }
        if (!empty($conditions)) {
            $query->where([
                'OR' => $conditions
            ]);
        }
        if ($this->_isRest()) {
            $organisations = $query->all();
            return $this->RestResponse->viewData($organisations, 'json');
        } else {
            $this->loadComponent('Paginator');
            $organisations = $this->Paginator->paginate($query);
            $this->set('data', $organisations);
            $this->set('metaGroup', 'ContactDB');
        }
    }

    public function add()
    {
        $organisation = $this->Organisations->newEmptyEntity();
        if ($this->request->is('post')) {
            $organisation = $this->Organisations->patchEntity($organisation, $this->request->getData());
            if ($this->Organisations->save($organisation)) {
                $message = __('Organisation added.');
                if ($this->_isRest()) {
                    $organisation = $this->Organisations->get($id);
                    return $this->RestResponse->viewData($organisation, 'json');
                } else {
                    $this->Flash->success($message);
                    $this->redirect(['action' => 'index']);
                }
            } else {
                $message = __('Organisation could not be added.');
                if ($this->_isRest()) {

                } else {
                    $this->Flash->error($message);
                }
            }
        }
        $this->set('metaGroup', 'ContactDB');
        $this->set('organisation', $organisation);
    }

    public function view($id)
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid organisation.'));
        }
        $organisation = $this->Organisations->get($id, [
            'contain' => ['Alignments' => 'Individuals']
        ]);
        if ($this->_isRest()) {
            return $this->RestResponse->viewData($organisation, 'json');
        } else {

        }
        $this->set('metaGroup', 'ContactDB');
        $this->set('organisation', $organisation);
    }

    public function edit($id)
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid organisation.'));
        }
        $organisation = $this->Organisations->get($id);
        if ($this->request->is(['post', 'put'])) {
            $this->Organisations->patchEntity($organisation, $this->request->getData());
            if ($this->Organisations->save($organisation)) {
                $message = __('Organisation updated.');
                if ($this->_isRest()) {
                    $organisation = $this->Organisations->get($id);
                    return $this->RestResponse->viewData($organisation, 'json');
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
        $this->set('organisation', $organisation);
        $this->render('add');
    }

    public function delete($id)
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid organisation.'));
        }
        $organisation = $this->Organisations->get($id);
        if ($this->request->is('post') || $this->request->is('delete')) {
            if ($this->Organisations->delete($organisation)) {
                $message = __('Organisation deleted.');
                if ($this->_isRest()) {
                    $organisation = $this->Organisations->get($id);
                    return $this->RestResponse->saveSuccessResponse('Organisations', 'delete', $id, 'json', $message);
                } else {
                    $this->Flash->success($message);
                    return $this->redirect($this->referer());
                }
            }
        }
        $this->set('metaGroup', 'ContactDB');
        $this->set('scope', 'organisations');
        $this->set('id', $organisation['id']);
        $this->set('organisation', $organisation);
        $this->viewBuilder()->setLayout('ajax');
        $this->render('/genericTemplates/delete');
    }
}
