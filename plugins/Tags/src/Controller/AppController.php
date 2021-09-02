<?php

namespace Tags\Controller;

use App\Controller\AppController as BaseController;

class AppController extends BaseController
{
    public function initialize(): void
    {
        parent::initialize();
        // $this->loadComponent('RequestHandler');
        // $this->loadComponent('ParamHandler', [
        //     'request' => $this->request
        // ]);
        // $this->loadComponent('CRUD', [
        //     'request' => $this->request,
        //     'table' => $this->{$this->modelClass},
        //     'MetaFields' => $this->MetaFields,
        //     'MetaTemplates' => $this->MetaTemplates
        // ]);
    }
}