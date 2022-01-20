<?php

namespace App\Controller;

use App\Controller\AppController;

class ApiController extends AppController
{
    /**
     * Controller action for displaying built-in Redoc UI
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $url = '/docs/openapi.yaml';
        $this->set('url', $url);
    }
}
