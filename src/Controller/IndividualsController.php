<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\TableRegistry;

class IndividualsController extends AppController
{
    public $quickFilterFields = ['uuid', ['email' => true], ['first_name' => true], ['last_name' => true], 'position'];
    public $filterFields = ['uuid', 'email', 'first_name', 'last_name', 'position', 'Organisations.id', 'Alignments.type'];
    public $containFields = ['Alignments' => 'Organisations'];
    public $statisticsFields = ['position'];

    public function index()
    {
        $currentUser = $this->ACL->getUser();
        $orgAdmin = !$currentUser['role']['perm_admin'] && $currentUser['role']['perm_org_admin'];
        $this->CRUD->index([
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
            'quickFilterForMetaField' => ['enabled' => true, 'wildcard_search' => true],
            'contain' => $this->containFields,
            'statisticsFields' => $this->statisticsFields,
            'afterFind' => function($data) use ($currentUser) {
                if ($currentUser['role']['perm_admin']) {
                    $data['user'] = $this->Individuals->Users->find()->select(['id', 'username', 'Organisations.id', 'Organisations.name'])->contain('Organisations')->where(['individual_id' => $data['id']])->all()->toArray();
                }
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $editableIds = null;
        if ($orgAdmin) {
            $editableIds = $this->Individuals->getValidIndividualsToEdit($currentUser);
        }
        $this->set('editableIds', $editableIds);
        $this->set('alignmentScope', 'individuals');
    }

    public function filtering()
    {
        $this->CRUD->filtering();
    }

    public function add()
    {
        $this->CRUD->add();
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function view($id)
    {
        $this->CRUD->view($id, ['contain' => ['Alignments' => 'Organisations']]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('canEdit', $this->canEdit($id));
    }

    public function edit($id)
    {
        if (!$this->canEdit($id)) {
            throw new MethodNotAllowedException(__('You cannot modify that individual.'));
        }
        $currentUser = $this->ACL->getUser();
        $this->CRUD->edit($id, [
            'beforeSave' => function($data) use ($currentUser) {
                if ($currentUser['role']['perm_admin'] && isset($data['uuid'])) {
                    unset($data['uuid']);
                }
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->render('add');
    }

    public function delete($id)
    {
        $this->CRUD->delete($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function tag($id)
    {
        if (!$this->canEdit($id)) {
            throw new MethodNotAllowedException(__('You cannot tag that individual.'));
        }
        $this->CRUD->tag($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function untag($id)
    {
        if (!$this->canEdit($id)) {
            throw new MethodNotAllowedException(__('You cannot untag that individual.'));
        }
        $this->CRUD->untag($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function viewTags($id)
    {
        $this->CRUD->viewTags($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function canEdit($indId): bool
    {
        $currentUser = $this->ACL->getUser();
        if ($currentUser['role']['perm_admin']) {
            return true;
        }
        $validIndividuals = $this->Individuals->getValidIndividualsToEdit($currentUser);
        if (in_array($indId, $validIndividuals)) {
            return true;
        }
        return false;
    }
}
