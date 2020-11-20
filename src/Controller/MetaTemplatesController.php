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

    public function toggle($id)
    {
        $template = $this->MetaTemplates->getTemplate($id);
        $template['enabled'] = $template['enabled'] ? 0 : 1;
        $result = $this->MetaTemplates->save($template);
        if ($template['enabled']) {
            $message = $result ? __('Template enabled.') : __('Could not enable template');
        } else {
            $message = $result ? __('Template disabled.') : __('Could not disable template');
        }
        if ($this->ParamHandler->isRest()) {
            if ($result) {
                return $this->RestResponse->saveSuccessResponse('MetaTemplates', 'toggle', $id, 'json', $message);
            } else {
                return $this->RestResponse->saveFailResponse('MetaTemplates', 'toggle', $id, 'json', $message);
            }
        } else {
            if ($this->Flash->{$result ? 'success' : 'error'}($message));
            $this->redirect($this->referer());
        }
    }
}
