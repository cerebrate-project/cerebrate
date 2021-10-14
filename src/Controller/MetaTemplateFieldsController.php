<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;

class MetaTemplateFieldsController extends AppController
{
    public $quickFilterFields = ['field', 'type'];
    public $filterFields = ['field', 'type', 'meta_template_id'];
    public $containFields = [];

    public function index()
    {
        $this->CRUD->index([
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'Administration');
    }
}
