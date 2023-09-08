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
            $this->paginate['contain'] = ['Individuals', 'Organisations'];
            $alignments = $this->paginate($query);
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
        if ($this->ParamHandler->isRest() || $this->ParamHandler->isAjax()) {
            return $this->RestResponse->viewData($individual, 'json');
        }
        $this->set('metaGroup', 'ContactDB');
        $this->set('alignment', $individual);
    }

    public function delete($id)
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid alignment.'));
        }
        $alignment = $this->Alignments->get($id);
        if (!$this->canEditIndividual($alignment->individual_id) || !$this->canEditOrganisation($alignment->organisation_id)) {
            throw new MethodNotAllowedException(__('You cannot delete this alignments.'));
        }
        if ($this->request->is('post') || $this->request->is('delete')) {
            if ($this->Alignments->delete($alignment)) {
                $message = __('Alignments deleted.');
                if ($this->ParamHandler->isRest() || $this->ParamHandler->isAjax()) {
                    return $this->RestResponse->saveSuccessResponse('Alignments', 'delete', $id, 'json', $message);
                } else {
                    $this->Flash->success($message);
                    return $this->redirect($this->referer());
                }
            }
        }
        $this->set('metaGroup', 'ContactDB');
        $this->set('scope', 'alignments');
        $this->set('id', $alignment['id']);
        $this->set('alignment', $alignment);
        $this->viewBuilder()->setLayout('ajax');
        $this->render('/genericTemplates/delete');
    }

    public function add($scope, $source_id)
    {
        if (empty($scope) || empty($source_id)) {
            throw new NotAcceptableException(__('Invalid input. scope and source_id expected as URL parameters in the format /alignments/add/[scope]/[source_id].'));
        }
        if (!in_array($scope, ['individuals', 'organisations'])) {
            throw new MethodNotAllowedException(__('Invalid scope. Should be `individuals` or `organisations`.'));
        }
        $this->loadModel('Individuals');
        $this->loadModel('Organisations');

        $validIndividualIDs = $this->Individuals->getValidIndividualsToEdit($this->ACL->getUser());
        $validOrgs = $this->Organisations->getEditableOrganisationsForUser($this->ACL->getUser());

        if ($scope == 'individuals' && !$this->canEditIndividual($source_id)) {
            throw new MethodNotAllowedException(__('You cannot modify that individual.'));
        } else if ($scope == 'organisations' && !$this->canEditOrganisation($source_id)) {
            throw new MethodNotAllowedException(__('You cannot modify that organisation.'));
        }

        $alignment = $this->Alignments->newEmptyEntity();
        if ($this->request->is('post')) {
            $this->Alignments->patchEntity($alignment, $this->request->getData());
            if ($scope === 'individuals') {
                $alignment['individual_id'] = $source_id;
            } else {
                $alignment['organisation_id'] = $source_id;
            }
            if ($scope == 'individuals' && !$this->canEditOrganisation($alignment['organisation_id'])) {
                throw new MethodNotAllowedException(__('You cannot use that organisation.'));
            } else if ($scope == 'organisations' && !$this->canEditIndividual($alignment['individual_id'])) {
                throw new MethodNotAllowedException(__('You cannot assign that individual.'));
            }
            $alignment = $this->Alignments->save($alignment);
            if ($alignment) {
                $message = __('Alignment added.');
                if ($this->ParamHandler->isRest()) {
                    return $this->RestResponse->viewData($alignment, 'json');
                } else if($this->ParamHandler->isAjax()) {
                    return $this->RestResponse->ajaxSuccessResponse('Alignment', 'add', $alignment, $message);
                } else {
                    $this->Flash->success($message);
                    $this->redirect($this->referer());
                }
            } else {
                $message = __('Alignment could not be added.');
                if ($this->ParamHandler->isRest() || $this->ParamHandler->isAjax()) {
                    return $this->RestResponse->saveFailResponse('Individuals', 'addAlignment', false, $message);
                } else {
                    $this->Flash->error($message);
                    //$this->redirect($this->referer());
                }
            }
        }
        if ($scope === 'organisations') {
            $individuals = $this->Individuals->find('list', ['valueField' => 'email'])->where(['id IN' => $validIndividualIDs])->toArray();
            $this->set('individuals', $individuals);
            $organisation = $this->Organisations->find()->where(['id' => $source_id])->first();
            if (empty($organisation)) {
                throw new NotFoundException(__('Invalid organisation'));
            }
            $this->set(compact('organisation'));
        } else {
            $organisations = Hash::combine($validOrgs, '{n}.id', '{n}.name');
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
    }

    private function canEditIndividual($indId): bool
    {
        $currentUser = $this->ACL->getUser();
        if ($currentUser['role']['perm_admin']) {
            return true;
        }
        $this->loadModel('Individuals');
        $validIndividuals = $this->Individuals->getValidIndividualsToEdit($currentUser);
        if (in_array($indId, $validIndividuals)) {
            return true;
        }
        return false;
    }

    private function canEditOrganisation($orgId): bool
    {
        $currentUser = $this->ACL->getUser();
        if ($currentUser['role']['perm_admin']) {
            return true;
        }
        if ($currentUser['role']['perm_org_admin'] && $currentUser['organisation']['id'] == $orgId) {
            return true;
        }
        return false;
    }
}
