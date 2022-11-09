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
    }

    public function edit($id)
    {
        $currentUser = $this->ACL->getUser();
        $validIndividualIds = [];
        if ($currentUser['role']['perm_admin']) {
            $validIndividualIds = $this->Individuals->getValidIndividualsToEdit($currentUser);
            if (!isset($validIndividualIds[$id])) {
                throw new NotFoundException(__('Invalid individual.'));
            }
        }
        $this->CRUD->edit($id);
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
        $this->CRUD->tag($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function untag($id)
    {
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
}
