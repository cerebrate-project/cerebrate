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
        if ($this->request->is('post')) {
            $result = $this->MetaTemplates->update();
            if ($this->ParamHandler->isRest()) {
                return $this->RestResponse->viewData($result, 'json');
            } else {
                $this->Flash->success(__('{0} templates updated.', count($result)));
                $this->redirect($this->referer());
            }
        } else {
            if (!$this->ParamHandler->isRest()) {
                $this->set('title', __('Update Meta Templates'));
                $this->set('question', __('Are you sure you wish to update the Meta Template definitions?'));
                $this->set('actionName', __('Update'));
                $this->set('path', ['controller' => 'metaTemplates', 'action' => 'update']);
                $this->render('/genericTemplates/confirm');
            }
        }
    }

    public function index()
    {
        $this->CRUD->index([
            'filters' => ['name', 'uuid', 'scope', 'namespace'],
            'quickFilters' => ['name', 'uuid', 'scope'],
            'contextFilters' => [
                'allow_all' => true,
                'fields' => ['scope'],
                'custom' => [
                    [
                        'label' => __('Contact DB'),
                        'filterCondition' => ['scope' => ['individual', 'organisation']]
                    ],
                    [
                        'label' => __('Namespace CNW'),
                        'filterCondition' => ['namespace' => 'cnw']
                    ],
                ]
            ],
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

    public function toggle($id)
    {
        $this->CRUD->toggle($id);
        if ($this->ParamHandler->isRest()) {
            return $this->restResponsePayload;
        } else if($this->ParamHandler->isAjax() && $this->request->is(['post', 'put'])) {
            return $this->ajaxResponsePayload;
        }
    }
}
