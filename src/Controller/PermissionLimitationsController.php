<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;

class PermissionLimitationsController extends AppController
{
    public $filterFields = ['scope', 'permission'];
    public $quickFilterFields = ['name'];
    public $containFields = [];

    public function index()
    {
        $this->CRUD->index([
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
            'afterFind' => function($data) {
                $data['comment'] = is_resource($data['comment']) ? stream_get_contents($data['comment']) : $data['comment'];
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'PermissionLimitations');
    }

    public function add()
    {
        $this->CRUD->add([
            'afterFind' => function($data) {
                $data['comment'] = is_resource($data['comment']) ? stream_get_contents($data['comment']) : $data['comment'];
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'PermissionLimitations');
    }

    public function view($id)
    {
        $this->CRUD->view($id, [
            'afterFind' => function($data) {
                $data['comment'] = is_resource($data['comment']) ? stream_get_contents($data['comment']) : $data['comment'];
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'PermissionLimitations');
    }

    public function edit($id)
    {
        $this->CRUD->edit($id, [
            'afterFind' => function($data) {
                $data['comment'] = is_resource($data['comment']) ? stream_get_contents($data['comment']) : $data['comment'];
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'PermissionLimitations');
        $this->render('add');
    }

    public function delete($id)
    {
        $this->CRUD->delete($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'PermissionLimitations');
    }
}
