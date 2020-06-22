<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Error\Debugger;

class AlignmentsController extends AppController
{
    public function index($organisation_id = null)
    {
        $query = $this->Alignments->find();
        if (!empty($organisation_id)) {
            $query->where(['organisation_id' => $organisation_id]);
        }
        if ($this->ParamHandler->isRest()) {
            $alignments = $query->all();
            return $this->RestResponse->viewData($alignments, 'json');
        } else {
            $this->loadComponent('Paginator');
            $alignments = $this->Paginator->paginate($query);
            $this->set('data', $alignments);
            $this->set('metaGroup', 'ContactDB');
        }
    }

    public function view($id)
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid alignment.'));
        }
        $individual = $this->Alignments->get($id);
        if ($this->ParamHandler->isRest()) {
            return $this->RestResponse->viewData($individual, 'json');
        } else {

        }
        $this->set('metaGroup', 'ContactDB');
        $this->set('alignment', $individual);
    }

    public function delete($id)
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid alignment.'));
        }
        $individual = $this->Alignments->get($id);
        if ($this->request->is('post') || $this->request->is('delete')) {
            if ($this->Alignments->delete($individual)) {
                $message = __('Individual deleted.');
                if ($this->ParamHandler->isRest()) {
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

    public function add($scope, $source_id)
    {
        if (empty($scope) || empty($source_id)) {
            throw new NotAcceptableException(__('Invalid input. scope and source_id expected as URL parameters in the format /alignments/add/[scope]/[source_id].'));
        }
        $this->loadModel('Individuals');
        $this->loadModel('Organisations');
        $alignment = $this->Alignments->newEmptyEntity();
        if ($this->request->is('post')) {
            $this->Alignments->patchEntity($alignment, $this->request->getData());
            if ($scope === 'individuals') {
                $alignment['individual_id'] = $source_id;
            } else {
                $alignment['organisation_id'] = $source_id;
            }
            if ($this->Alignments->save($alignment)) {
                $message = __('Alignment added.');
                if ($this->ParamHandler->isRest()) {
                    $alignment = $this->Alignments->get($this->Alignments->id);
                    return $this->RestResponse->viewData($alignment, 'json');
                } else {
                    $this->Flash->success($message);
                    $this->redirect($this->referer());
                }
            } else {
                $message = __('Alignment could not be added.');
                if ($this->ParamHandler->isRest()) {
                    return $this->RestResponse->saveFailResponse('Individuals', 'addAlignment', false, $message);
                } else {
                    $this->Flash->error($message);
                    //$this->redirect($this->referer());
                }
            }
        }
        if ($scope === 'organisations') {
            $individuals = $this->Individuals->find('list', ['valueField' => 'email']);
            $this->set('individuals', $individuals);
            $organisation = $this->Organisations->find()->where(['id' => $source_id])->first();
            if (empty($organisation)) {
                throw new NotFoundException(__('Invalid organisation'));
            }
            $this->set(compact('organisation'));
        } else {
            $organisations = $this->Organisations->find('list', ['valueField' => 'name']);
            $this->set('organisations', $organisations);
            $individual = $this->Individuals->find()->where(['id' => $source_id])->first();
            if (empty($individual)) {
                throw new NotFoundException(__('Invalid individual'));
            }
            $this->set(compact('individual'));
        }
        $this->set(compact('alignment'));
        $this->set('scope', $scope);
        $this->set('source_id', $source_id);
        $this->set('metaGroup', 'ContactDB');
    }
}
