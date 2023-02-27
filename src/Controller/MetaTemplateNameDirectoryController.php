<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\Utility\Inflector;
use Cake\ORM\TableRegistry;
use \Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Routing\Router;


class MetaTemplateNameDirectoryController extends AppController
{
    public $quickFilterFields = [['name' => true], 'uuid', 'version'];
    public $filterFields = ['name', 'uuid', 'version'];
    public $containFields = ['MetaTemplates'];


    public function index()
    {
        $this->CRUD->index([
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
            'contain' => $this->containFields,
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }
}
