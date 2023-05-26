<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;

class EnumerationCollectionsController extends AppController
{
    public $filterFields = ['name', 'target_model', 'target_field'];
    public $quickFilterFields = ['name', 'target_model', 'target_field'];
    public $containFields = [];

    public function index()
    {
        $this->CRUD->index([
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
            'contain' => ['Enumerations'],
            'afterFind' => function($data) {
                $data->value_count = isset($data->enumerations) ? count($data->enumerations) : 0;
                $data->values = Hash::extract($data, 'enumerations.{n}.value');
                unset($data->enumerations);
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'Enumerations');
    }

    public function add()
    {
        $this->CRUD->add([
            'afterSave' => function($data) {
                $this->EnumerationCollections->captureValues($data);
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set(compact('enumerations'));
        $this->set('metaGroup', 'Enumerations');
    }

    public function view($id)
    {
        $this->CRUD->view($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'Enumerations');
    }

    public function edit($id)
    {
        $this->CRUD->edit($id, [
            'afterSave' => function($data) {
                $this->EnumerationCollections->purgeValues($data);
                $this->EnumerationCollections->captureValues($data);
            },
            'contain' => ['Enumerations'],
            'afterFind' => function($data) {
                $values = [];
                foreach ($data['enumerations'] as $enumeration) {
                    $values[] = $enumeration['value'];
                }
                $data->values = implode(PHP_EOL, $values);
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'Enumerations');
        $this->render('add');
    }

    public function delete($id)
    {
        $this->CRUD->delete($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'Enumerations');
    }
}
