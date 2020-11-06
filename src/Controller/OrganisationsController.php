<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;

class OrganisationsController extends AppController
{
    public function index()
    {
        $this->CRUD->index([
            'filters' => ['name', 'uuid', 'nationality', 'sector', 'type', 'url', 'Alignments.id'],
            'quickFilters' => ['name', 'uuid', 'nationality', 'sector', 'type', 'url'],
            'contain' => ['Alignments' => 'Individuals']
        ]);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('alignmentScope', 'individuals');
        $this->set('metaGroup', 'ContactDB');
    }

    public function add()
    {
        $this->CRUD->add();
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
    }

    public function view($id)
    {
        $this->CRUD->view($id, ['contain' => ['Alignments' => 'Individuals']]);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
    }

    public function edit($id)
    {
        $this->CRUD->edit($id);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
        $this->render('add');
    }

    public function delete($id)
    {
        $this->CRUD->delete($id);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', 'ContactDB');
    }
}
