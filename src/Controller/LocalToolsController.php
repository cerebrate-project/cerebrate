<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;

class LocalToolsController extends AppController
{
    public function index()
    {
        $data = $this->LocalTools->extractMeta($this->LocalTools->getConnectors(), true);
        if ($this->ParamHandler->isRest()) {
            return $this->RestResponse->viewData($data, 'json');
        }
        $data = $this->CustomPagination->paginate($data);
        $this->set('data', $data);
        if ($this->request->is('ajax')) {
            $this->viewBuilder()->disableAutoLayout();
        }
        $this->set('metaGroup', 'LocalTools');
    }

    public function connectorIndex()
    {
        $this->CRUD->index([
            'filters' => ['name', 'connector'],
            'quickFilters' => ['name', 'connector'],
            'afterFind' => function($data) {
                foreach ($data as $connector) {
                    $connector['health'] = [$this->LocalTools->healthCheckIndividual($connector)];
                }
                return $data;
            }
        ]);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', 'LocalTools');
    }

    public function action()
    {
        $params = [];
        $results = $this->LocalTools->runAction();
        $this->render('add');
    }

    public function add()
    {
        $this->CRUD->add();
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $connectors = $this->LocalTools->extractMeta($this->LocalTools->getConnectors());
        $dropdownData = ['connectors' => []];
        foreach ($connectors as $connector) {
            $dropdownData['connectors'][$connector['connector']] = $connector['name'];
        }
        $this->set(compact('dropdownData'));
        $this->set('metaGroup', 'LocalTools');
    }

    public function viewConnector($connector_name)
    {
        $connectors = $this->LocalTools->extractMeta($this->LocalTools->getConnectors());
        $connector = false;
        foreach ($connectors as $c) {
            if ($connector === false || version_compare($c['version'], $connectors['version']) > 0) {
                $connector = $c;
            }
        }
        if ($this->ParamHandler->isRest()) {
            $this->restResponsePayload = $this->Controller->RestResponse->viewData($connector, 'json');
        }
        $this->set('entity', $connector);
        $this->set('metaGroup', 'LocalTools');
    }

    public function edit($id)
    {
        $this->CRUD->edit($id);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $connectors = $this->LocalTools->extractMeta($this->LocalTools->getConnectors());
        $dropdownData = ['connectors' => []];
        foreach ($connectors as $connector) {
            $dropdownData['connectors'][$connector['connector']] = $connector['name'];
        }
        $this->set(compact('dropdownData'));
        $this->set('metaGroup', 'LocalTools');
        $this->render('add');
    }

    public function delete($id)
    {
        $this->CRUD->delete($id);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', 'LocalTools');
    }

    public function view($id)
    {
        $this->CRUD->view($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'LocalTools');
    }

    public function test()
    {
        $connectors = $this->LocalTools->getConnectors();
        $connectors['MispConnector']->test();
    }
}
