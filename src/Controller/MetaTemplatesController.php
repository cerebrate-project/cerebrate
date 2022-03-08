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


class MetaTemplatesController extends AppController
{
    public $quickFilterFields = [['name' => true], 'uuid', ['scope' => true]];
    public $filterFields = ['name', 'uuid', 'scope', 'namespace'];
    public $containFields = ['MetaTemplateFields'];

    public function updateAllTemplates()
    {
        if ($this->request->is('post')) {
            $result = $this->MetaTemplates->updateAllTemplates();
            if ($this->ParamHandler->isRest()) {
                return $this->RestResponse->viewData($result, 'json');
            } else {
                if ($result['success']) {
                    $message = __n('{0} templates updated.', 'The template has been updated.', empty($template_id), $result['files_processed']);
                } else {
                    $message = __n('{0} templates could not be updated.', 'The template could not be updated.', empty($template_id), $result['files_processed']);
                }
                $this->CRUD->setResponseForController('updateAllTemplate', $result['success'], $message, $result['files_processed'], $result['update_errors'], ['redirect' => $this->referer()]);
                $responsePayload = $this->CRUD->getResponsePayload();
                if (!empty($responsePayload)) {
                    return $responsePayload;
                }
            }
        } else {
            if (!$this->ParamHandler->isRest()) {
                $this->set('title', __('Update All Meta Templates'));
                $this->set('question', __('Are you sure you wish to update all the Meta Template definitions'));
                $templatesUpdateStatus = $this->MetaTemplates->getUpdateStatusForTemplates();
                $this->set('templatesUpdateStatus', $templatesUpdateStatus);
                $this->render('updateAll');
            }
        }
    }

    /**
     * Update the provided template or all templates
     *
     * @param int|null $template_id
     */
    public function update($template_id=null)
    {
        $metaTemplate = false;
        if (!is_null($template_id)) {
            if (!is_numeric($template_id)) {
                throw new NotFoundException(__('Invalid {0} for provided ID.', $this->MetaTemplates->getAlias(), $template_id));
            }
            $metaTemplate = $this->MetaTemplates->find()->where([
                'id' => $template_id,
                'enabled' => 1
            ])->contain(['MetaTemplateFields'])->first();

            if (empty($metaTemplate)) {
                throw new NotFoundException(__('Invalid {0} {1}.', $this->MetaTemplates->getAlias(), $template_id));
            }
        }
        if ($this->request->is('post')) {
            $params = $this->ParamHandler->harvestParams(['update_strategy']);
            $updateStrategy = $params['update_strategy'] ?? null;
            $result = $this->MetaTemplates->update($metaTemplate, $updateStrategy);
            if ($this->ParamHandler->isRest()) {
                return $this->RestResponse->viewData($result, 'json');
            } else {
                if ($result['success']) {
                    $message = __n('{0} templates updated.', 'The template has been updated.', empty($template_id), $result['files_processed']);
                } else {
                    $message = __n('{0} templates could not be updated.', 'The template could not be updated.', empty($template_id), $result['files_processed']);
                }
                $this->CRUD->setResponseForController('update', $result['success'], $message, $result['files_processed'], $result['update_errors'], ['redirect' => $this->referer()]);
                $responsePayload = $this->CRUD->getResponsePayload();
                if (!empty($responsePayload)) {
                    return $responsePayload;
                }
            }
        } else {
            if (!$this->ParamHandler->isRest()) {
                if (!empty($metaTemplate)) {
                    $this->set('metaTemplate', $metaTemplate);
                    $statuses = $this->setUpdateStatus($metaTemplate->id);
                    $this->set('updateStatus', $this->MetaTemplates->computeFullUpdateStatusForMetaTemplate($statuses['templateStatus'], $metaTemplate));
                } else {
                    $this->set('title', __('Update All Meta Templates'));
                    $this->set('question', __('Are you sure you wish to update all the Meta Template definitions'));
                    $templatesUpdateStatus = $this->MetaTemplates->getUpdateStatusForTemplates();
                    $this->set('templatesUpdateStatus', $templatesUpdateStatus);
                    $this->render('updateAll');
                }
            }
        }
    }

    /**
     * Create a new template by loading the template on the disk having the provided UUID.
     *
     * @param string $uuid
     */
    public function createNewTemplate(string $uuid)
    {
        if ($this->request->is('post')) {
            $result = $this->MetaTemplates->createNewTemplate($uuid);
            if ($this->ParamHandler->isRest()) {
                return $this->RestResponse->viewData($result, 'json');
            } else {
                if ($result['success']) {
                    $message = __('The template {0} has been created.', $uuid);
                } else {
                    $message = __('The template {0} could not be created.', $uuid);
                }
                $this->CRUD->setResponseForController('createNewTemplate', $result['success'], $message, $result['files_processed'], $result['update_errors']);
                $responsePayload = $this->CRUD->getResponsePayload();
                if (!empty($responsePayload)) {
                    return $responsePayload;
                }
            }
        } else {
            if (!$this->ParamHandler->isRest()) {
                $this->set('title', __('Create Meta Template'));
                $this->set('question', __('Are you sure you wish to load the meta template with UUID: {0} in the database', h($uuid)));
                $this->set('actionName', __('Create template'));
                $this->set('path', ['controller' => 'meta-templates', 'action' => 'create_new_template', $uuid]);
                $this->render('/genericTemplates/confirm');
            }
        }
    }

    public function getMetaFieldsToUpdate($template_id)
    {
        $metaTemplate = $this->MetaTemplates->get($template_id);
        $newestMetaTemplate = $this->MetaTemplates->getNewestVersion($metaTemplate);
        $amountOfEntitiesToUpdate = 0;
        $entities = $this->MetaTemplates->getEntitiesHavingMetaFieldsFromTemplate($template_id, 10, $amountOfEntitiesToUpdate);
        $this->set('metaTemplate', $metaTemplate);
        $this->set('newestMetaTemplate', $newestMetaTemplate);
        $this->set('entities', $entities);
        $this->set('amountOfEntitiesToUpdate', $amountOfEntitiesToUpdate);
    }

    public function migrateOldMetaTemplateToNewestVersionForEntity($template_id, $entity_id)
    {
        $metaTemplate = $this->MetaTemplates->get($template_id, [
            'contain' => ['MetaTemplateFields']
        ]);
        $newestMetaTemplate = $this->MetaTemplates->getNewestVersion($metaTemplate, true);
        $entity = $this->MetaTemplates->getEntity($metaTemplate, $entity_id);
        $conditions = [
            'MetaFields.meta_template_id IN' => [$metaTemplate->id, $newestMetaTemplate->id],
            'MetaFields.scope' => $metaTemplate->scope,
        ];
        $keyedMetaFields = $this->MetaTemplates->getKeyedMetaFieldsForEntity($entity_id, $conditions);
        if (empty($keyedMetaFields[$metaTemplate->id])) {
            throw new NotFoundException(__('Invalid {0}. This entities does not have meta-fields to be moved to a newer template.', $this->MetaTemplates->getAlias()));
        }
        $mergedMetaFields = $this->MetaTemplates->insertMetaFieldsInMetaTemplates($keyedMetaFields, [$metaTemplate, $newestMetaTemplate]);
        $entity['MetaTemplates'] = $mergedMetaFields;
        if ($this->request->is('post') || $this->request->is('put')) {
            $className = Inflector::camelize(Inflector::pluralize($newestMetaTemplate->scope));
            $entityTable = TableRegistry::getTableLocator()->get($className);
            $inputData = $this->request->getData();
            $massagedData = $this->MetaTemplates->massageMetaFieldsBeforeSave($entity, $inputData, $newestMetaTemplate);
            unset($inputData['MetaTemplates']); // Avoid MetaTemplates to be overriden when patching entity
            $data = $massagedData['entity'];
            $metaFieldsToDelete = $massagedData['metafields_to_delete'];
            foreach ($entity->meta_fields as $i => $metaField) {
                if ($metaField->meta_template_id == $template_id) {
                    $metaFieldsToDelete[] = $entity->meta_fields[$i];
                }
            }
            $data = $entityTable->patchEntity($data, $inputData);
            $savedData = $entityTable->save($data);
            if ($savedData !== false) {
                if (!empty($metaFieldsToDelete)) {
                    $entityTable->MetaFields->unlink($savedData, $metaFieldsToDelete);
                }
                $message = __('Data on old meta-template has been migrated to newest meta-template');
            } else {
                $message = __('Could not migrate data to newest meta-template');
            }
            $this->CRUD->setResponseForController(
                'migrateOldMetaTemplateToNewestVersionForEntity',
                $savedData !== false,
                $message,
                $savedData,
                [],
                [
                    'redirect' => [
                        'controller' => $className,
                        'action' => 'view', $entity_id,
                        'url' => Router::url(['controller' => $className, 'action' => 'view', $entity_id])
                    ]
                ]
            );
            $responsePayload = $this->CRUD->getResponsePayload();
            if (!empty($responsePayload)) {
                return $responsePayload;
            }
        }
        $conflicts = $this->MetaTemplates->getMetaFieldsConflictsUnderTemplate($entity->meta_fields, $newestMetaTemplate);
        foreach ($conflicts as $conflict) {
            if (!empty($conflict['existing_meta_template_field'])) {
                $existingMetaTemplateField = $conflict['existing_meta_template_field'];
                foreach ($existingMetaTemplateField->metaFields as $metaField) {
                    $metaField->setError('value', implode(', ', $existingMetaTemplateField->conflicts));
                }
            }
        }
        // automatically convert non-conflicting fields to new meta-template
        $movedMetaTemplateFields = [];
        foreach ($metaTemplate->meta_template_fields as $metaTemplateField) {
            if (!empty($conflicts[$metaTemplateField->field]['conflicts'])) {
                continue;
            }
            foreach ($newestMetaTemplate->meta_template_fields as $i => $newMetaTemplateField) {
                if ($metaTemplateField->field == $newMetaTemplateField->field && empty($newMetaTemplateField->metaFields)) {
                    $movedMetaTemplateFields[] = $metaTemplateField->id;
                    $copiedMetaFields = array_map(function ($e) use ($newMetaTemplateField) {
                        $e = $e->toArray();
                        $e['meta_template_id'] = $newMetaTemplateField->meta_template_id;
                        $e['meta_template_field_id'] = $newMetaTemplateField->id;
                        unset($e['id']);
                        return $e;
                    }, $metaTemplateField->metaFields);
                    $newMetaTemplateField->metaFields = $this->MetaTemplates->MetaTemplateFields->MetaFields->newEntities($copiedMetaFields);
                }
            }
        }
        $this->set('oldMetaTemplate', $metaTemplate);
        $this->set('newMetaTemplate', $newestMetaTemplate);
        $this->set('entity', $entity);
        $this->set('conflicts', $conflicts);
        $this->set('movedMetaTemplateFields', $movedMetaTemplateFields);
    }

    public function index()
    {
        $templatesUpdateStatus = $this->MetaTemplates->getUpdateStatusForTemplates();
        $this->CRUD->index([
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
            'contextFilters' => [
                'custom' => [
                    [
                        'default' => true,
                        'label' => __('Newest Templates'),
                        'filterConditionFunction' => function ($query) {
                            return $query->where([
                                'id IN' => $this->MetaTemplates->genQueryForAllNewestVersionIDs()
                            ]);
                        }
                    ],
                ]
            ],
            'contain' => $this->containFields,
            'afterFind' => function($metaTemplate) use ($templatesUpdateStatus) {
                if (!empty($templatesUpdateStatus[$metaTemplate->uuid])) {
                    $templateStatus = $this->MetaTemplates->getStatusForMetaTemplate($templatesUpdateStatus[$metaTemplate->uuid]['template'], $metaTemplate);
                    $metaTemplate->set('updateStatus', $this->MetaTemplates->computeFullUpdateStatusForMetaTemplate($templateStatus, $metaTemplate));
                }
                return $metaTemplate;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $updateableTemplates = [
            'not-up-to-date' => $this->MetaTemplates->getNotUpToDateTemplates(),
            'can-be-removed' => $this->MetaTemplates->getCanBeRemovedTemplates(),
            'new' => $this->MetaTemplates->getNewTemplates(),
        ];
        $this->set('defaultTemplatePerScope', $this->MetaTemplates->getDefaultTemplatePerScope());
        $this->set('alignmentScope', 'individuals');
        $this->set('updateableTemplates', $updateableTemplates);
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

    public function delete($id)
    {
        $metaTemplate = $this->MetaTemplates->get($id, [
            'contain' => ['MetaTemplateFields']
        ]);
        $templateOnDisk = $this->MetaTemplates->readTemplateFromDisk($metaTemplate->uuid);
        $templateStatus = $this->MetaTemplates->getStatusForMetaTemplate($templateOnDisk, $metaTemplate);
        if (empty($templateStatus['can-be-removed'])) {
            throw new MethodNotAllowedException(__('This meta-template cannot be removed'));
        }
        $this->set('deletionText', __('The meta-template "{0}" has no meta-field and can be safely removed.', h($templateStatus['existing_template']->name)));
        $this->CRUD->delete($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
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

    private function getUpdateStatus($id): array
    {
        $metaTemplate = $this->MetaTemplates->get($id, [
            'contain' => ['MetaTemplateFields']
        ]);
        $templateOnDisk = $this->MetaTemplates->readTemplateFromDisk($metaTemplate->uuid);
        $templateStatus = $this->MetaTemplates->getStatusForMetaTemplate($templateOnDisk, $metaTemplate);
        return $templateStatus;
    }

    /**
     * Retreive the template stored on disk and compute the status for the provided template id.
     *
     * @param [type] $id
     * @return array
     */
    private function setUpdateStatus($template_id): array
    {
        $metaTemplate = $this->MetaTemplates->get($template_id, [
            'contain' => ['MetaTemplateFields']
        ]);
        $templateOnDisk = $this->MetaTemplates->readTemplateFromDisk($metaTemplate->uuid);
        $templateStatus = $this->MetaTemplates->getStatusForMetaTemplate($templateOnDisk, $metaTemplate);
        $this->set('templateOnDisk', $templateOnDisk);
        $this->set('templateStatus', $templateStatus);
        return [
            'templateOnDisk' => $templateOnDisk,
            'templateStatus' => $templateStatus,
        ];
    }
}
