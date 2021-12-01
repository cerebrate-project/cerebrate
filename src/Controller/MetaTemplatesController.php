<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;

class MetaTemplatesController extends AppController
{
    public $quickFilterFields = [['name' => true], 'uuid', ['scope' => true]];
    public $filterFields = ['name', 'uuid', 'scope', 'namespace'];
    public $containFields = ['MetaTemplateFields'];

    public function update($template_id=false)
    {
        if (!empty($template_id)) {
            $metaTemplate = $this->MetaTemplates->get($template_id);
        }
        if ($this->request->is('post')) {
            $result = $this->MetaTemplates->update($template_id);
            if ($this->ParamHandler->isRest()) {
                return $this->RestResponse->viewData($result, 'json');
            } else {
                if ($result['success']) {
                    $message = __n('{0} templates updated.', 'The template has been updated.', empty($template_id), $result['updated']);
                } else {
                    $message = __n('{0} templates could not be updated.', 'The template could not be updated.',empty($template_id), $result['updated']);
                }
                $this->CRUD->setResponseForController('update', $result['success'], $message, $metaTemplate, $metaTemplate->getErrors(), ['redirect' => $this->referer()]);
                $responsePayload = $this->CRUD->getResponsePayload();
                if (!empty($responsePayload)) {
                    return $responsePayload;
                }
            }
        } else {
            if (!$this->ParamHandler->isRest()) {
                if (!empty($template_id)) {
                    $this->set('metaTemplate', $metaTemplate);
                    $this->setUpdateStatus($metaTemplate->id);
                } else {
                    $this->set('title', __('Update Meta Templates'));
                    $this->set('question', __('Are you sure you wish to update the Meta Template definitions'));
                    $updateableTemplates = $this->MetaTemplates->checkForUpdates();
                    $this->set('updateableTemplates', $updateableTemplates);
                    $this->render('updateAll');
                }
            }
        }
    }

    public function index()
    {
        $updateableTemplate = $this->MetaTemplates->checkForUpdates();
        $this->CRUD->index([
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
            'contextFilters' => [
                'fields' => ['scope'],
                'custom' => [
                    [
                        'label' => __('Default Templates'),
                        'filterCondition' => ['is_default' => true]
                    ],
                ]
            ],
            'contain' => $this->containFields,
            'afterFind' => function($data) use ($updateableTemplate) {
                foreach ($data as $i => $metaTemplate) {
                    if (!empty($updateableTemplate[$metaTemplate->uuid])) {
                        $metaTemplate->set('status', $this->MetaTemplates->getTemplateStatus($updateableTemplate[$metaTemplate->uuid]));
                    }
                }
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $updateableTemplate = [
            'not_up_to_date' => $this->MetaTemplates->getNotUpToDateTemplates(),
            'new' => $this->MetaTemplates->getNewTemplates(),
        ];
        $this->set('defaultTemplatePerScope', $this->MetaTemplates->getDefaultTemplatePerScope());
        $this->set('alignmentScope', 'individuals');
        $this->set('updateableTemplate', $updateableTemplate);
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
        $this->setUpdateStatus($id);
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

    public function setUpdateStatus($id)
    {
        $metaTemplate = $this->MetaTemplates->get($id);
        $templateOnDisk = $this->MetaTemplates->readTemplateFromDisk($metaTemplate->uuid);
        $updateableTemplate = $this->MetaTemplates->checkUpdatesForTemplate($templateOnDisk);
        $this->set('updateableTemplate', $updateableTemplate);
        $this->set('templateOnDisk', $templateOnDisk);
    }
}
