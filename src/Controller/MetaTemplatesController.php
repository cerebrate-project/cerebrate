<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;

class MetaTemplatesController extends AppController
{
    public function update()
    {
        $result = $this->MetaTemplates->update();
        return $this->RestResponse->viewData($result, 'json');
    }

    public function index()
    {
        $this->CRUD->index([
            'filters' => ['name', 'uuid', 'scope'],
            'quickFilters' => ['name', 'uuid', 'scope'],
            'contain' => ['MetaTemplateFields']
        ]);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('alignmentScope', 'individuals');
        $this->set('metaGroup', 'Administration');
    }

    public function view($id)
    {
        $this->CRUD->view($id, [
            'contain' => ['MetaTemplateFields']
        ]);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', 'Administration');
    }
}
