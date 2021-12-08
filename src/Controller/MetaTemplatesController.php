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

class MetaTemplatesController extends AppController
{
    public $quickFilterFields = [['name' => true], 'uuid', ['scope' => true]];
    public $filterFields = ['name', 'uuid', 'scope', 'namespace'];
    public $containFields = ['MetaTemplateFields'];

    public function update($template_uuid=null)
    {
        $metaTemplate = false;
        if (!is_null($template_uuid)) {
            $metaTemplate = $this->MetaTemplates->find()->where([
                'uuid' => $template_uuid
            ])->first();
            if (empty($metaTemplate)) {
                throw new NotFoundException(__('Invalid {0}.', $this->MetaTemplates->getAlias()));
            }
        }
        if ($this->request->is('post')) {
            $updateStrategy = $this->request->getData('update_strategy', null);
            $result = $this->MetaTemplates->update($template_uuid, $updateStrategy);
            if ($this->ParamHandler->isRest()) {
                return $this->RestResponse->viewData($result, 'json');
            } else {
                if ($result['success']) {
                    $message = __n('{0} templates updated.', 'The template has been updated.', empty($template_uuid), $result['files_processed']);
                } else {
                    $message = __n('{0} templates could not be updated.', 'The template could not be updated.', empty($template_uuid), $result['files_processed']);
                }
                $this->CRUD->setResponseForController('update', $result['success'], $message, $result['files_processed'], $result['update_errors'], ['redirect' => $this->referer()]);
                $responsePayload = $this->CRUD->getResponsePayload();
                if (!empty($responsePayload)) {
                    return $responsePayload;
                }
            }
        } else {
            if (!$this->ParamHandler->isRest()) {
                if (!is_null($template_uuid)) {
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

    public function getMetaFieldsToUpdate($template_id)
    {
        $metaTemplate = $this->MetaTemplates->get($template_id);
        $newestMetaTemplate = $this->MetaTemplates->getNewestVersion($metaTemplate);
        $entities = $this->MetaTemplates->getEntitiesWithMetaFieldsToUpdate($template_id);
        $this->set('metaTemplate', $metaTemplate);
        $this->set('newestMetaTemplate', $newestMetaTemplate);
        $this->set('entities', $entities);
    }

    public function migrateOldMetaTemplateToNewestVersionForEntity($template_id, $entity_id)
    {
        $metaTemplate = $this->MetaTemplates->get($template_id, [
            'contain' => ['MetaTemplateFields']
        ]);
        $newestMetaTemplate = $this->MetaTemplates->getNewestVersion($metaTemplate, true);
        $entity = $this->MetaTemplates->migrateMetaTemplateToNewVersion($metaTemplate, $newestMetaTemplate, $entity_id);
        $conditions = [
            'MetaFields.meta_template_id IN' => [$metaTemplate->id, $newestMetaTemplate->id]
        ];
        $keyedMetaFields = $this->MetaTemplates->getKeyedMetaFields($metaTemplate->scope, $entity_id, $conditions);
        if (empty($keyedMetaFields[$metaTemplate->id])) {
            throw new NotFoundException(__('Invalid {0}. This entities does not have meta-fields to be moved to a newer template.', $this->MetaTemplates->getAlias()));
        }
        $mergedMetaFields = $this->MetaTemplates->mergeMetaFieldsInMetaTemplate($keyedMetaFields, [$metaTemplate, $newestMetaTemplate]);
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
                ['redirect' => [
                    'controller' => $className,
                    'action' => 'view', $entity_id]
                ]
            );
            $responsePayload = $this->CRUD->getResponsePayload();
            if (!empty($responsePayload)) {
                return $responsePayload;
            }
        }
        $conflicts = $this->MetaTemplates->checkForMetaTemplateConflicts($metaTemplate, $newestMetaTemplate);
        foreach ($conflicts as $conflict) {
            $existingMetaTemplateField = $conflict['existing_meta_template_field'];
            foreach ($existingMetaTemplateField->metaFields as $metafield_id => $metaField) {
                $metaField->setError('value', implode(', ', $existingMetaTemplateField->conflicts));
            }
        }
        // automatically convert non-conflicting fields to new meta-template
        $movedMetaTemplateFields = [];
        foreach ($metaTemplate->meta_template_fields as $metaTemplateField) {
            if (!empty($conflicts[$metaTemplateField->field]['conflicts'])) {
                continue;
            }
            foreach ($newestMetaTemplate->meta_template_fields as $newMetaTemplateField) {
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
                        $updateStatusForTemplate = $this->MetaTemplates->checkUpdateForMetaTemplate($updateableTemplate[$metaTemplate->uuid]['template'], $metaTemplate);
                        $metaTemplate->set('status', $this->MetaTemplates->getTemplateStatus($updateStatusForTemplate, $metaTemplate));
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
            'can_be_removed' => $this->MetaTemplates->getCanBeRemovedTemplates(),
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

    public function delete($id)
    {
        $updateableTemplate = $this->getUpdateStatus($id);
        if (empty($updateableTemplate['can_be_removed'])) {
            throw MethodNotAllowedException(__('This meta-template cannot be removed'));
        }
        $this->set('deletionText', __('The meta-template "{0}" has no meta-field and can be safely removed.', h($updateableTemplate['existing_template']->name)));
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
        $updateableTemplate = $this->MetaTemplates->checkUpdateForMetaTemplate($templateOnDisk, $metaTemplate);
        return $updateableTemplate;
    }

    public function setUpdateStatus($id)
    {
        $metaTemplate = $this->MetaTemplates->get($id, [
            'contain' => ['MetaTemplateFields']
        ]);
        $templateOnDisk = $this->MetaTemplates->readTemplateFromDisk($metaTemplate->uuid);
        $updateableTemplate = $this->MetaTemplates->checkUpdateForMetaTemplate($templateOnDisk, $metaTemplate);
        $this->set('updateableTemplate', $updateableTemplate);
        $this->set('templateOnDisk', $templateOnDisk);
    }
}
