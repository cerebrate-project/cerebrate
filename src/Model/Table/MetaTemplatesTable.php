<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Utility\Text;

class MetaTemplatesTable extends AppTable
{
    public const TEMPLATE_PATH = [
        ROOT . '/libraries/default/meta_fields/',
        ROOT . '/libraries/custom/meta_fields/'
    ];
    
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('Timestamp');
        $this->hasMany(
            'MetaTemplateFields',
            [
                'foreignKey' => 'meta_template_id',
                'saveStrategy' => 'replace',
                'dependent' => true,
                'cascadeCallbacks' => true,
            ]
        );
        $this->setDisplayField('name');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('scope')
            ->notEmptyString('name')
            ->notEmptyString('namespace')
            ->notEmptyString('uuid')
            ->notEmptyString('version')
            ->notEmptyString('source')
            ->requirePresence(['scope', 'source', 'version', 'uuid', 'name', 'namespace'], 'create');
        return $validator;
    }

    public function update($template_uuid=null, $strategy=null)
    {
        $files_processed = [];
        $readErrors = [];
        $preUpdateChecks = [];
        $updatesErrors = [];
        $templates = $this->readTemplatesFromDisk($readErrors);
        foreach ($templates as $template) {
            $updateStatus = $this->checkForUpdates($template['uuid']);
            $preUpdateChecks[$template['uuid']] = $updateStatus;
            if (is_null($template_uuid) || $template_uuid == $template['uuid']) {
                $errors = [];
                $success = false;
                if ($updateStatus['up-to-date']) {
                    $errors['message'] = __('Meta-template already up-to-date');
                    $success = true;
                } else if ($updateStatus['new']) {
                    $success = $this->saveNewMetaTemplate($template, $errors);
                } else if ($updateStatus['updateable']) {
                    $success = $this->updateMetaTemplate($template, $errors);
                } else if (!$updateStatus['up-to-date'] && is_null($strategy)) {
                    $errors['message'] = __('Cannot update meta-template, update strategy not provided');
                } else if (!$updateStatus['up-to-date'] && !is_null($strategy)) {
                    $success = $this->updateMetaTemplateWithStrategy($template, $strategy, $errors);
                } else {
                    $errors['message'] = __('Could not update. Something went wrong.');
                }
                if ($success) {
                    $files_processed[] = $template['uuid'];
                }
                if (!empty($errors)) {
                    $updatesErrors[] = $errors;
                }
            }
        }
        $results = [
            'read_errors' => $readErrors,
            'pre_update_errors' => $preUpdateChecks,
            'update_errors' => $updatesErrors,
            'files_processed' => $files_processed,
            'success' => !empty($files_processed),
        ];
        return $results;
    }

    public function checkForUpdates($template_uuid=null): array
    {
        $templates = $this->readTemplatesFromDisk($readErrors);
        $result = [];
        foreach ($templates as $template) {
            if (is_null($template_uuid)) {
                $result[$template['uuid']] = $this->checkUpdatesForTemplate($template);
            } else if ($template['uuid'] == $template_uuid) {
                $result = $this->checkUpdatesForTemplate($template);
                return $result;
            }
        }
        return $result;
    }

    public function isUpToDate(array $updateResult): bool
    {
        return $updateResult['up-to-date'] || $updateResult['new'];
    }

    public function isUpdateable(array $updateResult): bool
    {
        return $updateResult['updateable'];
    }

    public function isNew(array $updateResult): bool
    {
        return $updateResult['new'];
    }

    public function hasNoConflict(array $updateResult): bool
    {
        return $this->hasConflict($updateResult);
    }

    public function hasConflict(array $updateResult): bool
    {
        return !$updateResult['updateable'] && !$updateResult['up-to-date'] && !$updateResult['new'];
    }

    public function isUpdateableToExistingMetaTemplate($metaTemplate): bool
    {
        $newestTemplate = $this->getNewestVersion($metaTemplate);
        return !empty($newestTemplate);
    }

    public function isRemovable(array $updateResult): bool
    {
        return !empty($updateResult['can_be_removed']);
    }

    public function getTemplateStatus(array $updateResult, $metaTemplate): array
    {
        return [
            'up_to_date' => $this->isUpToDate($updateResult),
            'updateable' => $this->isUpdateable($updateResult),
            'is_new' => $this->isNew($updateResult),
            'has_conflict' => $this->hasConflict($updateResult),
            'to_existing' => $this->isUpdateableToExistingMetaTemplate($metaTemplate),
            'can_be_removed' => $this->isRemovable($updateResult),
        ];
    }

    public function getUpToDateTemplates($result=null): array
    {
        $result = is_null($result) ? $this->checkForUpdates() : $result;
        foreach ($result as $i => $updateResult) {
            if (!$this->isUpToDate($updateResult)) {
                unset($result[$i]);
            }
        }
        return $result;
    }

    public function getNotUpToDateTemplates($result=null): array
    {
        $result = is_null($result) ? $this->checkForUpdates() : $result;
        foreach ($result as $i => $updateResult) {
            if ($this->isUpToDate($updateResult)) {
                unset($result[$i]);
            }
        }
        return $result;
    }

    public function getUpdateableTemplates($result=null): array
    {
        $result = is_null($result) ? $this->checkForUpdates() : $result;
        foreach ($result as $i => $updateResult) {
            if (!$this->isUpdateable($updateResult)) {
                unset($result[$i]);
            }
        }
        return $result;
    }

    public function getNewTemplates($result=null): array
    {
        $result = is_null($result) ? $this->checkForUpdates() : $result;
        foreach ($result as $i => $updateResult) {
            if (!$this->isNew($updateResult)) {
                unset($result[$i]);
            }
        }
        return $result;
    }

    public function getConflictTemplates($result=null): array
    {
        $result = is_null($result) ? $this->checkForUpdates() : $result;
        foreach ($result as $i => $updateResult) {
            if (!$this->hasConflict($updateResult)) {
                unset($result[$i]);
            }
        }
        return $result;
    }

    public function getNewestVersion($metaTemplate, $full=false)
    {
        $query = $this->find()->where([
            'uuid' => $metaTemplate->uuid,
            'id !=' => $metaTemplate->id,
            'version >=' => $metaTemplate->version,
        ])
        ->order(['version' => 'DESC']);
        if ($full) {
            $query->contain(['MetaTemplateFields']);
        }
        $newestTemplate = $query->first();
        return $newestTemplate;
    }

    public function getCanBeRemovedTemplates($result=null): array
    {
        $result = is_null($result) ? $this->checkForUpdates() : $result;
        foreach ($result as $i => $updateResult) {
            if (!$this->isRemovable($updateResult)) {
                unset($result[$i]);
            }
        }
        return $result;
    }

    public function readTemplatesFromDisk(&$errors=[]): array
    {
        $templates = [];
        $errors = [];
        foreach (self::TEMPLATE_PATH as $path) {
            if (is_dir($path)) {
                $files = scandir($path);
                foreach ($files as $k => $file) {
                    if (substr($file, -5) === '.json') {
                        $errorMessage = '';
                        $metaTemplate = $this->decodeTemplateFromDisk($path . $file, $errorMessage);
                        if (!empty($metaTemplate)) {
                            $templates[] = $metaTemplate;
                        } else {
                            $errors[] = $errorMessage;
                        }
                    }
                }
            }
        }
        return $templates;
    }

    public function readTemplateFromDisk(string $uuid, &$error=''): ?array
    {
        foreach (self::TEMPLATE_PATH as $path) {
            if (is_dir($path)) {
                $files = scandir($path);
                foreach ($files as $k => $file) {
                    if (substr($file, -5) === '.json') {
                        $errorMessage = '';
                        $metaTemplate = $this->decodeTemplateFromDisk($path . $file, $errorMessage);
                        if (!empty($metaTemplate) && $metaTemplate['uuid'] == $uuid) {
                            return $metaTemplate;
                        }
                    }
                }
            }
        }
        $error = __('Could not find meta-template with UUID {0}', $uuid);
        return null;
    }

    public function decodeTemplateFromDisk(string $filePath, &$errorMessage=''): ?array
    {
        if (file_exists($filePath)) {
            $explodedPath = explode('/', $filePath);
            $filename = $explodedPath[count($explodedPath)-1];
            $contents = file_get_contents($filePath);
            $metaTemplate = json_decode($contents, true);
            if (empty($metaTemplate)) {
                $errorMessage = __('Could not load template file `{0}`. Error while decoding the template\'s JSON', $filename);
                return null;
            }
            if (empty($metaTemplate['uuid']) || empty($metaTemplate['version'])) {
                $errorMessage = __('Could not load template file. Invalid template file. Missing template UUID or version');
                return null;
            }
            return $metaTemplate;
        }
    }

    public function getEntitiesWithMetaFieldsToUpdate(int $template_id): array
    {
        $metaTemplate = $this->get($template_id);
        $queryParentEntities = $this->MetaTemplateFields->MetaFields->find();
        $queryParentEntities
            ->select(['parent_id'])
            ->where([
                'meta_template_id' => $template_id
            ])
            ->group(['parent_id']);

        $entitiesClassName = Inflector::camelize(Inflector::pluralize($metaTemplate->scope));
        $entitiesTable = TableRegistry::getTableLocator()->get($entitiesClassName);
        $entityQuery = $entitiesTable->find()
            ->where(['id IN' => $queryParentEntities])
            ->contain([
                'MetaFields' => [
                    'conditions' => [
                        'meta_template_id' => $template_id
                    ]
                ]
            ]);
        $entities = $entityQuery->all()->toList();
        return $entities;
    }

    public function getKeyedMetaFields(string $scope, int $entity_id, array $conditions=[])
    {
        $query = $this->MetaTemplateFields->MetaFields->find();
        $query->where(array_merge(
            $conditions,
            [
                'MetaFields.scope' => $scope,
                'MetaFields.parent_id' => $entity_id
            ]
        ));
        $metaFields = $query->all();
        $data = [];
        foreach ($metaFields as $metaField) {
            if (empty($data[$metaField->meta_template_id][$metaField->meta_template_field_id])) {
                $data[$metaField->meta_template_id][$metaField->meta_template_field_id] = [];
            }
            $data[$metaField->meta_template_id][$metaField->meta_template_field_id][$metaField->id] = $metaField;
        }
        return $data;
    }

    public function mergeMetaFieldsInMetaTemplate(array $keyedMetaFields, array $metaTemplates)
    {
        $merged = [];
        foreach ($metaTemplates as $metaTemplate) {
            $metaTemplate['meta_template_fields'] = Hash::combine($metaTemplate['meta_template_fields'], '{n}.id', '{n}');
            $merged[$metaTemplate->id] = $metaTemplate;
            if (isset($keyedMetaFields[$metaTemplate->id])) {
                foreach ($metaTemplate->meta_template_fields as $j => $meta_template_field) {
                    if (isset($keyedMetaFields[$metaTemplate->id][$meta_template_field->id])) {
                        $merged[$metaTemplate->id]->meta_template_fields[$j]['metaFields'] = $keyedMetaFields[$metaTemplate->id][$meta_template_field->id];
                    } else {
                        $merged[$metaTemplate->id]->meta_template_fields[$j]['metaFields'] = [];
                    }
                }
            }
        }
        return $merged;
    }

    public function migrateMetaTemplateToNewVersion(\App\Model\Entity\MetaTemplate $oldMetaTemplate, \App\Model\Entity\MetaTemplate $newMetaTemplate, int $entityId)
    {
        $entitiesClassName = Inflector::camelize(Inflector::pluralize($oldMetaTemplate->scope));
        $entitiesTable = TableRegistry::getTableLocator()->get($entitiesClassName);
        $entity = $entitiesTable->get($entityId, [
            'contain' => 'MetaFields'
        ]);
        return $entity;
    }

    public function getTemplate($id)
    {
        $query = $this->find();
        $query->where(['id' => $id]);
        $template = $query->first();
        if (empty($template)) {
            throw new NotFoundException(__('Invalid template ID specified.'));
        }
        return $template;
    }

    public function getDefaultTemplatePerScope(String $scope = '')
    {
        $query = $this->find('list', [
            'keyField' => 'scope',
            'valueField' => function ($template) {
                return $template;
            }
        ])->where(['is_default' => true]);
        if (!empty($scope)) {
            $query->where(['scope' => $scope]);
        }
        return $query->all()->toArray();
    }

    public function removeDefaultFlag(String $scope)
    {
        $this->updateAll(
            ['is_default' => false],
            ['scope' => $scope]
        );
    }

    public function saveNewMetaTemplate(array $template, array &$errors=[], &$savedMetaTemplate=null): bool
    {
        $template['meta_template_fields'] = $template['metaFields'];
        unset($template['metaFields']);
        $metaTemplate = $this->newEntity($template, [
            'associated' => ['MetaTemplateFields']
        ]);
        $tmp = $this->save($metaTemplate, [
            'associated' => ['MetaTemplateFields']
        ]);
        $error = null;
        if (empty($tmp)) {
            $error = new UpdateError();
            $error->success = false;
            $error->message = __('Could not save the template.');
            $error->errors = $metaTemplate->getErrors();
            $errors[] = $error;
        }
        $savedMetaTemplate = $tmp;
        return !is_null($error);
    }

    public function updateMetaTemplate(array $template, array &$errors=[]): bool
    {
        $metaTemplate = $this->getMetaTemplateElligibleForUpdate($template);
        if (is_string($metaTemplate)) {
            $errors[] = new UpdateError(false, $metaTemplate);
            return false;
        }
        $metaTemplate = $this->patchEntity($metaTemplate, $template, [
            'associated' => ['MetaTemplateFields']
        ]);
        $metaTemplate = $this->save($metaTemplate, [
            'associated' => ['MetaTemplateFields']
        ]);
        if (!empty($metaTemplate)) {
            $errors[] = new UpdateError(false, __('Could not save the template.'), $metaTemplate->getErrors());
            return false;
        }
        return true;
    }

    public function updateMetaTemplateWithStrategy(array $template, string $strategy, array $errors=[]): bool
    {
        $metaTemplate = $this->getMetaTemplateElligibleForUpdate($template);
        if (is_string($metaTemplate)) {
            $errors[] = new UpdateError(false, $metaTemplate);
            return false;
        }
        $success = $this->executeUpdateStrategy($strategy, $template, $metaTemplate);
        if (is_string($success)) {
            $errors[] = new UpdateError(false, $success);
            return false;
        }
        return true;
    }

    public function getMetaTemplateElligibleForUpdate($template)
    {
        $query = $this->find()
            ->contain('MetaTemplateFields')->where([
                'uuid' => $template['uuid']
            ]);
        $metaTemplate = $query->first();
        if (empty($metaTemplate)) {
            return __('Meta-template not found.');
        }
        if ($metaTemplate->version >= $template['version']) {
            return __('Could not update the template. Local version is newer.');
        }
        return $metaTemplate;
    }

    public function executeUpdateStrategy(string $strategy, array $template, \App\Model\Entity\MetaTemplate $metaTemplate)
    {
        if ($strategy == 'keep_both') {
            $result = $this->executeStrategyKeep($template, $metaTemplate);
        } else if ($strategy == 'delete_all') {
            $result = $this->executeStrategyDeleteAll($template, $metaTemplate);
        } else {
            return __('Invalid strategy {0}', $strategy);
        }
        if (is_string($result)) {
            return $result;
        }
        return true;
    }

    // Old template remains untouched
    // Create new template
    // Migrate all non-conflicting meta-fields for one entity to the new template
    // Keep all the conflicting meta-fields for one entity on the old template
    public function executeStrategyKeep(array $template, \App\Model\Entity\MetaTemplate $metaTemplate)
    {
        $savedMetaTemplate = null;
        $conflicts = $this->checkForMetaTemplateConflicts($metaTemplate, $template);
        $blockingConflict = Hash::extract($conflicts, '{s}.conflicts');
        $errors = [];
        if (empty($blockingConflict)) { // No conflict, everything can be updated without special care
            $this->updateMetaTemplate($template, $errors);
            return !empty($errors) ? $errors[0] : true;
        }
        $entities = $this->fetchEntitiesWithMetaFieldsForTemplate($metaTemplate);

        $conflictingEntities = [];
        foreach ($entities as $entity) {
            $conflicts = $this->checkMetaFieldsValidityUnderTemplate($entity['meta_fields'], $template);
            if (!empty($conflicts)) {
                $conflictingEntities[$entity->id] = $entity->id;
            }
        }
        if (empty($conflictingEntities)) {
            $this->updateMetaTemplate($template, $errors);
            return !empty($errors) ? $errors[0] : true;
        }
        $template['is_default'] = $metaTemplate['is_default'];
        $template['enabled'] = $metaTemplate['enabled'];
        if ($metaTemplate->is_default) {
            $metaTemplate->set('is_default', false);
            $this->save($metaTemplate);
        }
        $success = $this->saveNewMetaTemplate($template, $errors, $savedMetaTemplate);
        if (!empty($savedMetaTemplate)) { // conflicting entities remain untouched
            $savedMetaTemplateFieldByName = Hash::combine($savedMetaTemplate['meta_template_fields'], '{n}.field', '{n}');
            foreach ($entities as $entity) {
                if (empty($conflictingEntities[$entity->id])) {
                    foreach ($entity['meta_fields'] as $metaField) {
                        $savedMetaTemplateField = $savedMetaTemplateFieldByName[$metaField->field];
                        $success = $this->replaceMetaTemplate($metaField, $savedMetaTemplateField);
                    }
                }
            }
        } else {
            return $errors[0]->message;
        }
        return true;
    }

    // Delete conflicting meta-fields
    // Update template to the new version
    public function executeStrategyDeleteAll($template, $metaTemplate)
    {
        $errors = [];
        $conflicts = $this->checkForMetaTemplateConflicts($metaTemplate, $template);
        $blockingConflict = Hash::extract($conflicts, '{s}.conflicts');
        if (empty($blockingConflict)) { // No conflict, everything can be updated without special care
            $this->updateMetaTemplate($template, $errors);
            return !empty($errors) ? $errors[0] : true;
        }
        $entities = $this->fetchEntitiesWithMetaFieldsForTemplate($metaTemplate);

        foreach ($entities as $entity) {
            $conflicts = $this->checkMetaFieldsValidityUnderTemplate($entity['meta_fields'], $template);
            $result = $this->MetaTemplateFields->MetaFields->deleteAll([
                'id IN' => $conflicts
            ]);
        }
        $this->updateMetaTemplate($template, $errors);
        return !empty($errors) ? $errors[0] : true;
    }

    public function replaceMetaTemplate(\App\Model\Entity\MetaField $metaField,  \App\Model\Entity\MetaTemplateField $savedMetaTemplateField)
    {
        $metaField->set('meta_template_id', $savedMetaTemplateField->meta_template_id);
        $metaField->set('meta_template_field_id', $savedMetaTemplateField->id);
        $metaField = $this->MetaTemplateFields->MetaFields->save($metaField);
        return !empty($metaField);
    }

    public function checkMetaFieldsValidityUnderTemplate(array $metaFields, array $template): array
    {
        $conflicting = [];
        $metaTemplateFieldByName = [];
        foreach ($template['metaFields']  as $metaField) {
            $metaTemplateFieldByName[$metaField['field']] = $this->MetaTemplateFields->newEntity($metaField);
        }
        foreach ($metaFields as $metaField) {
            $isValid = $this->MetaTemplateFields->MetaFields->isValidMetaFieldForMetaTemplateField(
                $metaField->value,
                $metaTemplateFieldByName[$metaField->field]
            );
            if ($isValid !== true) {
                $conflicting[] = $metaField;
            }
        }
        return $conflicting;
    }

    public function checkMetaFieldsValidityUnderExistingMetaTemplate(array $metaFields, \App\Model\Entity\MetaTemplate $metaTemplate): array
    {
        $conflicting = [];
        $metaTemplateFieldByName = [];
        foreach ($metaTemplate->meta_template_fields  as $metaTemplateField) {
            $metaTemplateFieldByName[$metaTemplateField->field] = $metaTemplateField;
        }
        foreach ($metaFields as $metaField) {
            if ($metaField->meta_template_id != $metaTemplate->id) {
                continue;
            }
            $isValid = $this->MetaTemplateFields->MetaFields->isValidMetaFieldForMetaTemplateField(
                $metaField->value,
                $metaTemplateFieldByName[$metaField->field]
            );
            if ($isValid !== true) {
                $conflicting[] = $metaField;
            }
        }
        return $conflicting;
    }

    public function fetchEntitiesWithMetaFieldsForTemplate(\App\Model\Entity\MetaTemplate $metaTemplate): array
    {
        $entitiesIDWithMetaFields = $this->MetaTemplateFields->MetaFields->find()
            ->select(['parent_id', 'scope'])
            ->where(['MetaFields.meta_template_id' => $metaTemplate->id])
            ->group('parent_id')
            ->all()
            ->toList();
        $className = Inflector::camelize(Inflector::pluralize($entitiesIDWithMetaFields[0]->scope));

        $table = TableRegistry::getTableLocator()->get($className);
        $entities = $table->find()
            ->where(['id IN' => Hash::extract($entitiesIDWithMetaFields, '{n}.parent_id')])
            ->contain([
                'MetaFields' => [
                    'conditions' => [
                        'MetaFields.meta_template_id' => $metaTemplate->id
                    ]
                ]
            ])
            ->all()->toList();

        return $entities;
    }

    public function checkForMetaFieldConflicts(\App\Model\Entity\MetaTemplateField $metaTemplateField, array $templateField): array
    {
        $result = [
            'updateable' => true,
            'conflicts' => [],
            'conflictingEntities' => [],
        ];
        if ($metaTemplateField->multiple && $templateField['multiple'] == false) { // Field is no longer multiple
            $query = $this->MetaTemplateFields->MetaFields->find();
            $query
                ->enableHydration(false)
                ->select([
                    'parent_id',
                    'meta_template_field_id',
                    'count' => $query->func()->count('meta_template_field_id'),
                ])
                ->where([
                    'meta_template_field_id' => $metaTemplateField->id,
                ])
                ->group(['parent_id'])
                ->having(['count >' => 1]);
            $conflictingStatus = $query->all()->toList();
            if (!empty($conflictingStatus)) {
                $result['updateable'] = false;
                $result['conflicts'][] = __('This field is no longer multiple');
                $result['conflictingEntities'] = Hash::extract($conflictingStatus, '{n}.parent_id');
            }
        }
        if (!empty($templateField['regex']) && $templateField['regex'] != $metaTemplateField->regex) {
            $query = $this->MetaTemplateFields->MetaFields->find();
            $query
                ->enableHydration(false)
                ->select([
                    'parent_id',
                    'scope',
                    'meta_template_field_id',
                ])
                ->where([
                    'meta_template_field_id' => $metaTemplateField->id,
                ]);
            $entitiesWithMetaField = $query->all()->toList();
            if (!empty($entitiesWithMetaField)) {
                $className = Inflector::camelize(Inflector::pluralize($entitiesWithMetaField[0]['scope']));
                $table = TableRegistry::getTableLocator()->get($className);
                $entities = $table->find()
                    ->where(['id IN' => Hash::extract($entitiesWithMetaField, '{n}.parent_id')])
                    ->contain([
                        'MetaFields' => [
                            'conditions' => [
                                'MetaFields.meta_template_field_id' => $metaTemplateField->id
                            ]
                        ]
                    ])
                    ->all()->toList();
                $conflictingEntities = [];
                foreach ($entities as $entity) {
                    foreach ($entity['meta_fields'] as $metaField) {
                        $isValid = $this->MetaTemplateFields->MetaFields->isValidMetaFieldForMetaTemplateField(
                            $metaField->value,
                            $templateField
                        );
                        if ($isValid !== true) {
                            $conflictingEntities[] = $entity->id;
                            break;
                        }
                    }
                }

                if (!empty($conflictingEntities)) {
                    $result['updateable'] = $result['updateable'] && false;
                    $result['conflicts'][] = __('This field is instantiated with values not passing the validation anymore');
                    $result['conflictingEntities'] = $conflictingEntities;
                }
            }
        }
        return $result;
    }

    public function checkForMetaTemplateConflicts(\App\Model\Entity\MetaTemplate $metaTemplate, $template): array
    {
        $templateMetaFields = [];
        if (!is_array($template) && get_class($template) == 'App\Model\Entity\MetaTemplate') {
            $templateMetaFields = $template->meta_template_fields;
        } else {
            $templateMetaFields = $template['metaFields'];
        }
        $conflicts = [];
        $existingMetaTemplateFields = Hash::combine($metaTemplate->toArray(), 'meta_template_fields.{n}.field');
        foreach ($templateMetaFields as $newMetaField) {
            foreach ($metaTemplate->meta_template_fields as $metaField) {
                if ($newMetaField['field'] == $metaField->field) {
                    unset($existingMetaTemplateFields[$metaField->field]);
                    $templateConflicts = $this->checkForMetaFieldConflicts($metaField, !is_array($newMetaField) && get_class($newMetaField) == 'App\Model\Entity\MetaTemplateField' ? $newMetaField->toArray() : $newMetaField);
                    $conflicts[$metaField->field] = $templateConflicts;
                    $conflicts[$metaField->field]['existing_meta_template_field'] = $metaField;
                    $conflicts[$metaField->field]['existing_meta_template_field']['conflicts'] = $templateConflicts['conflicts'];
                }
            }
        }
        if (!empty($existingMetaTemplateFields)) {
            foreach ($existingMetaTemplateFields as $field => $tmp) {
                $conflicts[$field] = [
                    'updateable' => false,
                    'conflicts' => [__('This field is intended to be removed')],
                ];
            }
        }
        return $conflicts;
    }

    public function checkUpdatesForTemplate($template, $metaTemplate=null): array
    {
        $result = [
            'new' => true,
            'up-to-date' => false,
            'updateable' => false,
            'conflicts' => [],
            'template' => $template,
        ];
        if (is_null($metaTemplate)) {
            $query = $this->find()
                ->contain('MetaTemplateFields')
                ->where([
                    'uuid' => $template['uuid'],
                ])
                ->order(['version' => 'DESC']);
            $metaTemplate = $query->first();
        }
        debug($metaTemplate);
        if (!empty($metaTemplate)) {
            $result['existing_template'] = $metaTemplate;
            $result['current_version'] = $metaTemplate->version;
            $result['next_version'] = $template['version'];
            $result['new'] = false;
            if ($metaTemplate->version >= $template['version']) {
                $result['up-to-date'] = true;
                $result['updateable'] = false;
                $result['conflicts'][] = __('Could not update the template. Local version is equal or newer.');
                return $result;
            }
            
            $conflicts = $this->checkForMetaTemplateConflicts($metaTemplate, $template);
            if (!empty($conflicts)) {
                $result['conflicts'] = $conflicts;
            } else {
                $result['updateable'] = true;
            }
        }
        return $result;
    }

    public function checkUpdateForMetaTemplate($template, $metaTemplate): array
    {
        $result = $this->checkUpdatesForTemplate($template, $metaTemplate);
        $result['meta_field_amount'] = $this->MetaTemplateFields->MetaFields->find()->where(['meta_template_id' => $metaTemplate->id])->count();
        $result['can_be_removed'] = empty($result['meta_field_amount']) && empty($result['to_existing']);
        return $result;
    }

    public function massageMetaFieldsBeforeSave($entity, $input, $metaTemplate)
    {
        $metaFieldsTable = $this->MetaTemplateFields->MetaFields;
        $className = Inflector::camelize(Inflector::pluralize($metaTemplate->scope));
        $entityTable = TableRegistry::getTableLocator()->get($className);
        $metaFieldsIndex = [];
        if (!empty($entity->meta_fields)) {
            foreach ($entity->meta_fields as $i => $metaField) {
                $metaFieldsIndex[$metaField->id] = $i;
            }
        } else {
            $entity->meta_fields = [];
        }

        $metaFieldsToDelete = [];
        foreach ($input['MetaTemplates'] as $template_id => $template) {
            foreach ($template['meta_template_fields'] as $meta_template_field_id => $meta_template_field) {
                $rawMetaTemplateField = $metaTemplate->meta_template_fields[$meta_template_field_id];
                foreach ($meta_template_field['metaFields'] as $meta_field_id => $meta_field) {
                    if ($meta_field_id == 'new') { // create new meta_field
                        $new_meta_fields = $meta_field;
                        foreach ($new_meta_fields as $new_value) {
                            if (!empty($new_value)) {
                                $metaField = $metaFieldsTable->newEmptyEntity();
                                $metaFieldsTable->patchEntity($metaField, [
                                    'value' => $new_value,
                                    'scope' => $entityTable->getBehavior('MetaFields')->getScope(),
                                    'field' => $rawMetaTemplateField->field,
                                    'meta_template_id' => $rawMetaTemplateField->meta_template_id,
                                    'meta_template_field_id' => $rawMetaTemplateField->id,
                                    'parent_id' => $entity->id,
                                    'uuid' => Text::uuid(),
                                ]);
                                $entity->meta_fields[] = $metaField;
                                $entity->MetaTemplates[$template_id]->meta_template_fields[$meta_template_field_id]->metaFields[] = $metaField;
                            }
                        }
                    } else {
                        $new_value = $meta_field['value'];
                        if (!empty($new_value)) { // update meta_field and attach validation errors
                            if (!empty($metaFieldsIndex[$meta_field_id])) {
                                $index = $metaFieldsIndex[$meta_field_id];
                                $metaFieldsTable->patchEntity($entity->meta_fields[$index], [
                                    'value' => $new_value, 'meta_template_field_id' => $rawMetaTemplateField->id
                                ], ['value']);
                                $metaFieldsTable->patchEntity(
                                    $entity->MetaTemplates[$template_id]->meta_template_fields[$meta_template_field_id]->metaFields[$meta_field_id],
                                    ['value' => $new_value, 'meta_template_field_id' => $rawMetaTemplateField->id],
                                    ['value']
                                );
                            } else { // metafield comes from a second post where the temporary entity has already been created
                                $metaField = $metaFieldsTable->newEmptyEntity();
                                $metaFieldsTable->patchEntity($metaField, [
                                    'value' => $new_value,
                                    'scope' => $entityTable->getBehavior('MetaFields')->getScope(), // get scope from behavior
                                    'field' => $rawMetaTemplateField->field,
                                    'meta_template_id' => $rawMetaTemplateField->meta_template_id,
                                    'meta_template_field_id' => $rawMetaTemplateField->id,
                                    'parent_id' => $entity->id,
                                    'uuid' => Text::uuid(),
                                ]);
                                $entity->meta_fields[] = $metaField;
                                $entity->MetaTemplates[$template_id]->meta_template_fields[$meta_template_field_id]->metaFields[] = $metaField;
                            }
                        } else { // Metafield value is empty, indicating the field should be removed
                            $index = $metaFieldsIndex[$meta_field_id];
                            $metaFieldsToDelete[] = $entity->meta_fields[$index];
                            unset($entity->meta_fields[$index]);
                            unset($entity->MetaTemplates[$template_id]->meta_template_fields[$meta_template_field_id]->metaFields[$meta_field_id]);
                        }
                    }
                }
            }
        }

        $entity->setDirty('meta_fields', true);
        return ['entity' => $entity, 'metafields_to_delete' => $metaFieldsToDelete];
    }
}

class UpdateError 
{
    public $success;
    public $message = '';
    public $errors = [];

    public function __construct($success=false, $message='', $errors=[])
    {
        $this->success = $success;
        $this->message = $message;
        $this->errors = $errors;
    }
}