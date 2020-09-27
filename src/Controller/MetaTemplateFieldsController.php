<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;

class MetaTemplateFieldsController extends AppController
{
    public function index()
    {
        $this->CRUD->index([
            'filters' => ['field', 'type', 'meta_template_id'],
            'quickFilters' => ['field', 'type']
        ]);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        }
        $this->set('metaGroup', 'Administration');
    }
}
