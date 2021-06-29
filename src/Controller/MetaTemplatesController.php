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
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('defaultTemplatePerScope', $this->MetaTemplates->getDefaultTemplatePerScope());
        $this->set('alignmentScope', 'individuals');
        $this->set('metaGroup', 'Administration');
    }

    public function view($id)
    {
        $this->CRUD->view($id, [
            'contain' => ['MetaTemplateFields']
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'Administration');
    }

    public function toggle($id, $fieldName = 'enabled')
    {
        if ($this->request->is('POST') && $fieldName == 'is_default') {
            $template = $this->MetaTemplates->get($id);
            $this->MetaTemplates->removeDefaultFlag($template->scope);
            $this->CRUD->toggle($id, $fieldName, ['force_state' => !$template->is_default]);
        } else {
            $this->CRUD->toggle($id, $fieldName);
        }
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }
}
