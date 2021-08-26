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
        $this->CRUD->index([
            'filters' => ['uuid', 'email', 'first_name', 'last_name', 'position', 'Organisations.id', 'Alignments.type'],
            'quickFilters' => ['uuid', 'email', 'first_name', 'last_name', 'position'],
            'contextFilters' => [
                'fields' => [
                    'Alignments.type'
                ]
            ],
            'contain' => ['Alignments' => 'Organisations']
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('alignmentScope', 'individuals');
        $this->set('metaGroup', 'ContactDB');
    }

    public function add()
    {
        $this->CRUD->add();
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
    }

    public function view($id)
    {
        $this->CRUD->view($id, ['contain' => ['Alignments' => 'Organisations']]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
    }

    public function edit($id)
    {
        $this->CRUD->edit($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
        $this->render('add');
    }

    public function delete($id)
    {
        $this->CRUD->delete($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
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
