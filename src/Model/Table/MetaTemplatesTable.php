<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\Filesystem\File;
use Cake\Filesystem\Folder;

class MetaTemplatesTable extends AppTable
{
    public const TEMPLATE_PATH = [
        ROOT . '/libraries/default/meta_fields/',
        ROOT . '/libraries/custom/meta_fields/'
    ];

    public const UPDATE_STRATEGY_CREATE_NEW = 'create_new';
    public const UPDATE_STRATEGY_UPDATE_EXISTING = 'update_existing';
    public const UPDATE_STRATEGY_KEEP_BOTH = 'keep_both';
    public const UPDATE_STRATEGY_DELETE = 'delete_all';

    public $ALLOWED_STRATEGIES = [MetaTemplatesTable::UPDATE_STRATEGY_CREATE_NEW];

    private $templatesOnDisk = null;

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

    public function isStrategyAllowed(string $strategy): bool
    {
        return in_array($strategy, $this->ALLOWED_STRATEGIES);
    }

    // /**
    //  * Load the template stored on the disk for the provided id and update it using the optional strategy.
    //  *
    //  * @param int $template_id
    //  * @param string|null $strategy The strategy to be used when updating templates with conflicts
    //  * @return array The update result containing potential errors and the successes
    //  */
    // public function update($template_id, $strategy = null): array
    // {
    //     $files_processed = [];
    //     $readErrors = [];
    //     $preUpdateChecks = [];
    //     $updatesErrors = [];
    //     $templates = $this->readTemplatesFromDisk($readErrors);
    //     foreach ($templates as $template) {
    //         $updateStatus = $this->getUpdateStatusForTemplates($template['uuid']);
    //         $preUpdateChecks[$template['uuid']] = $updateStatus;
    //         if (is_null($template_uuid) || $template_uuid == $template['uuid']) {
    //             $errors = [];
    //             $success = false;
    //             if ($updateStatus['up-to-date']) {
    //                 $errors['message'] = __('Meta-template already up-to-date');
    //                 $success = true;
    //             } else if ($this->isStrategyAllowed(MetaTemplatesTable::UPDATE_STRATEGY_CREATE_NEW) && $updateStatus['new']) {
    //                 $success = $this->saveNewMetaTemplate($template, $errors);
    //             } else if ($this->isStrategyAllowed(MetaTemplatesTable::UPDATE_STRATEGY_UPDATE_EXISTING) && $updateStatus['automatically-updateable']) {
    //                 $success = $this->updateMetaTemplate($template, $errors);
    //             } else if (!$updateStatus['up-to-date'] && (is_null($strategy) || !$this->isStrategyAllowed($strategy))) {
    //                 $errors['message'] = __('Cannot update meta-template, update strategy not provided or not allowed');
    //             } else if (!$updateStatus['up-to-date'] && !is_null($strategy)) {
    //                 $success = $this->updateMetaTemplateWithStrategyRouter($template, $strategy, $errors);
    //             } else {
    //                 $errors['message'] = __('Could not update. Something went wrong.');
    //             }
    //             if ($success) {
    //                 $files_processed[] = $template['uuid'];
    //             }
    //             if (!empty($errors)) {
    //                 $updatesErrors[] = $errors;
    //             }
    //         }
    //     }
    //     $results = [
    //         'read_errors' => $readErrors,
    //         'pre_update_errors' => $preUpdateChecks,
    //         'update_errors' => $updatesErrors,
    //         'files_processed' => $files_processed,
    //         'success' => !empty($files_processed),
    //     ];
    //     return $results;
    // }

    /**
     * Load the templates stored on the disk update and create them in the database without touching at the existing ones
     *
     * @param \App\Model\Entity\MetaTemplate $metaTemplate
     * @return array The update result containing potential errors and the successes
     */
    public function updateAllTemplates(): array
    {
        $updatesErrors = [];
        $files_processed = [];
        $templatesOnDisk = $this->readTemplatesFromDisk();
        $templatesUpdateStatus = $this->getUpdateStatusForTemplates();
        foreach ($templatesOnDisk as $template) {
            $errors = [];
            $success = false;
            $updateStatus = $templatesUpdateStatus[$template['uuid']];
            if ($this->isStrategyAllowed(MetaTemplatesTable::UPDATE_STRATEGY_CREATE_NEW) && $updateStatus['new']) {
                $success = $this->saveNewMetaTemplate($template, $errors);
            }
            if ($success) {
                $files_processed[] = $template['uuid'];
            } else {
                $updatesErrors[] = $errors;
            }
        }
        $results = [
            'update_errors' => $updatesErrors,
            'files_processed' => $files_processed,
            'success' => !empty($files_processed),
        ];
        return $results;
    }

    /**
     * Load the template stored on the disk for the provided meta-template and update it using the optional strategy.
     *
     * @param \App\Model\Entity\MetaTemplate $metaTemplate
     * @param string|null $strategy The strategy to be used when updating templates with conflicts
     * @return array The update result containing potential errors and the successes
     */
    public function update($metaTemplate, $strategy = null): array
    {
        $files_processed = [];
        $updatesErrors = [];
        $templateOnDisk = $this->readTemplateFromDisk($metaTemplate->uuid);
        $templateStatus = $this->getStatusForMetaTemplate($templateOnDisk, $metaTemplate);
        $updateStatus = $this->computeFullUpdateStatusForMetaTemplate($templateStatus, $metaTemplate);
        $errors = [];
        $success = false;
        if ($updateStatus['up-to-date']) {
            $errors['message'] = __('Meta-template already up-to-date');
            $success = true;
        } else if ($this->isStrategyAllowed(MetaTemplatesTable::UPDATE_STRATEGY_CREATE_NEW) && $updateStatus['new']) {
            $success = $this->saveNewMetaTemplate($templateOnDisk, $errors);
        } else if ($this->isStrategyAllowed(MetaTemplatesTable::UPDATE_STRATEGY_UPDATE_EXISTING) && $updateStatus['automatically-updateable']) {
            $success = $this->updateMetaTemplate($metaTemplate, $templateOnDisk, $errors);
        } else if (!$updateStatus['up-to-date'] && (is_null($strategy) || !$this->isStrategyAllowed($strategy))) {
            $errors['message'] = __('Cannot update meta-template, update strategy not provided or not allowed');
        } else if (!$updateStatus['up-to-date'] && !is_null($strategy)) {
            $success = $this->updateMetaTemplateWithStrategyRouter($metaTemplate, $templateOnDisk, $strategy, $errors);
        } else {
            $errors['message'] = __('Could not update. Something went wrong.');
        }
        if ($success) {
            $files_processed[] = $templateOnDisk['uuid'];
        }
        if (!empty($errors)) {
            $updatesErrors[] = $errors;
        }
        $results = [
            'update_errors' => $updatesErrors,
            'files_processed' => $files_processed,
            'success' => !empty($files_processed),
        ];
        return $results;
    }

    /**
     * Load the templates stored on the disk update and create the one having the provided UUID in the database
     * Will do nothing if the UUID is already known
     *
     * @param string $uuid
     * @return array The update result containing potential errors and the successes
     */
    public function createNewTemplate(string $uuid): array
    {
        $templateOnDisk = $this->readTemplateFromDisk($uuid);
        $templateStatus = $this->getUpdateStatusForTemplate($templateOnDisk);
        $errors = [];
        $updatesErrors = [];
        $files_processed = [];
        $savedMetaTemplate = null;
        $success = false;
        if (empty($templateStatus['new'])) {
            $error['message'] = __('Template UUID already exists');
            $success = true;
        } else if ($this->isStrategyAllowed(MetaTemplatesTable::UPDATE_STRATEGY_CREATE_NEW)) {
            $success = $this->saveNewMetaTemplate($templateOnDisk, $errors, $savedMetaTemplate);
        } else {
            $errors['message'] = __('Could not create template. Something went wrong.');
        }
        if ($success) {
            $files_processed[] = $templateOnDisk['uuid'];
        }
        if (!empty($errors)) {
            $updatesErrors[] = $errors;
        }
        $results = [
            'update_errors' => $updatesErrors,
            'files_processed' => $files_processed,
            'success' => !empty($files_processed),
        ];
        return $results;
    }

    /**
     * Load the templates stored on the disk and compute their update status.
     * Only compute the result if an UUID is provided
     *
     * @param string|null $template_uuid
     * @return array
     */
    public function getUpdateStatusForTemplates(): array
    {
        $errors = [];
        $templateUpdatesStatus = [];
        $templates = $this->readTemplatesFromDisk($errors);
        foreach ($templates as $template) {
            $templateUpdatesStatus[$template['uuid']] = $this->getUpdateStatusForTemplate($template);
        }
        return $templateUpdatesStatus;
    }


    /**
     * Checks if the template is update-to-date from the provided update status
     *
     * @param array $updateStatus
     * @return boolean
     */
    public function isUpToDate(array $updateStatus): bool
    {
        return !empty($updateStatus['up-to-date']) || !empty($updateStatus['new']);
    }

    /**
     * Checks if the template is updateable automatically from the provided update status
     *
     * @param array $updateStatus
     * @return boolean
     */
    public function isAutomaticallyUpdateable(array $updateStatus): bool
    {
        return !empty($updateStatus['automatically-updateable']);
    }

    /**
     * Checks if the template is new (and not loaded in the database yet) from the provided update status
     *
     * @param array $updateStatus
     * @return boolean
     */
    public function isNew(array $updateStatus): bool
    {
        return $updateStatus['new'];
    }

    /**
     * Checks if the template has no conflicts that would prevent an automatic update from the provided update status
     *
     * @param array $updateStatus
     * @return boolean
     */
    public function hasNoConflict(array $updateStatus): bool
    {
        return $this->hasConflict($updateStatus);
    }

    /**
     * Checks if the template has conflict preventing an automatic update from the provided update status
     *
     * @param array $updateStatus
     * @return boolean
     */
    public function hasConflict(array $updateStatus): bool
    {
        return empty($updateStatus['automatically-updateable']) && empty($updateStatus['up-to-date']) && empty($updateStatus['new']);
    }

    /**
     * Checks if the metaTemplate can be updated to a newer version loaded in the database
     *
     * @param \App\Model\Entity\MetaTemplate $metaTemplate
     * @return boolean
     */
    public function isUpdateableToExistingMetaTemplate(\App\Model\Entity\MetaTemplate $metaTemplate): bool
    {
        $newestTemplate = $this->getNewestVersion($metaTemplate);
        return !empty($newestTemplate);
    }

    /**
     * Checks if the template can be removed from the database for the provided update status.
     * A template can be removed if a newer version is already loaded in the database and no meta-fields are using it.
     *
     * @param array $updateStatus
     * @return boolean
     */
    public function isRemovable(array $updateStatus): bool
    {
        return !empty($updateStatus['can-be-removed']);
    }

    /**
     * Compute the state from the provided update status and metaTemplate
     *
     * @param array $updateStatus
     * @param \App\Model\Entity\MetaTemplate $metaTemplate
     * @return array
     */
    public function computeFullUpdateStatusForMetaTemplate(array $updateStatus, \App\Model\Entity\MetaTemplate $metaTemplate): array
    {
        return [
            'up-to-date' => $this->isUpToDate($updateStatus),
            'automatically-updateable' => $this->isAutomaticallyUpdateable($updateStatus),
            'is-new' => $this->isNew($updateStatus),
            'has-conflict' => $this->hasConflict($updateStatus),
            'to-existing' => $this->isUpdateableToExistingMetaTemplate($metaTemplate),
            'can-be-removed' => $this->isRemovable($updateStatus),
        ];
    }

    /**
     * Get the update status of meta-templates that are up-to-date in regards to the template stored on the disk.
     *
     * @param array|null $updateStatus
     * @return array The list of update status for up-to-date templates
     */
    public function getUpToDateTemplates($updatesStatus = null): array
    {
        $updatesStatus = is_null($updatesStatus) ? $this->getUpdateStatusForTemplates() : $updatesStatus;
        foreach ($updatesStatus as $uuid => $updateStatus) {
            if (!$this->isUpToDate($updateStatus)) {
                unset($updatesStatus[$uuid]);
            }
        }
        return $updatesStatus;
    }

    /**
     * Get the update status of meta-templates that are not up-to-date in regards to the template stored on the disk.
     *
     * @param array|null $updateResult
     * @return array The list of update status for non up-to-date templates
     */
    public function getNotUpToDateTemplates($updatesStatus = null): array
    {
        $updatesStatus = is_null($updatesStatus) ? $this->getUpdateStatusForTemplates() : $updatesStatus;
        foreach ($updatesStatus as $uuid => $updateStatus) {
            if ($this->isUpToDate($updateStatus)) {
                unset($updatesStatus[$uuid]);
            }
        }
        return $updatesStatus;
    }

    /**
     * Get the update status of meta-templates that are automatically updateable in regards to the template stored on the disk.
     *
     * @param array|null $updateResult
     * @return array The list of update status for non up-to-date templates
     */
    public function getAutomaticallyUpdateableTemplates($updatesStatus = null): array
    {
        $updatesStatus = is_null($updatesStatus) ? $this->getUpdateStatusForTemplates() : $updatesStatus;
        foreach ($updatesStatus as $uuid => $updateStatus) {
            if (!$this->isAutomaticallyUpdateable($updateStatus)) {
                unset($updatesStatus[$uuid]);
            }
        }
        return $updatesStatus;
    }

    /**
     * Get the update status of meta-templates that are new in regards to the template stored on the disk.
     *
     * @param array|null $updateResult
     * @return array The list of update status for new templates
     */
    public function getNewTemplates($updatesStatus = null): array
    {
        $updatesStatus = is_null($updatesStatus) ? $this->getUpdateStatusForTemplates() : $updatesStatus;
        foreach ($updatesStatus as $uuid => $updateStatus) {
            if (!$this->isNew($updateStatus)) {
                unset($updatesStatus[$uuid]);
            }
        }
        return $updatesStatus;
    }

    /**
     * Get the update status of meta-templates that have conflict preventing an automatic update in regards to the template stored on the disk.
     *
     * @param array|null $updateResult
     * @return array The list of update status for new templates
     */
    public function getConflictTemplates($updatesStatus = null): array
    {
        $updatesStatus = is_null($updatesStatus) ? $this->getUpdateStatusForTemplates() : $updatesStatus;
        foreach ($updatesStatus as $uuid => $updateStatus) {
            if (!$this->hasConflict($updateStatus)) {
                unset($updatesStatus[$uuid]);
            }
        }
        return $updatesStatus;
    }

    /**
     * Get the latest (having the higher version) meta-template loaded in the database for the provided meta-template
     *
     * @param \App\Model\Entity\MetaTemplate $metaTemplate
     * @param boolean $full
     * @return \App\Model\Entity\MetaTemplate|null
     */
    public function getNewestVersion(\App\Model\Entity\MetaTemplate $metaTemplate, bool $full = false)
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

    /**
     * Generate and return a query (to be used as a subquery) resolving to the IDs of the latest version of a saved meta-template
     *
     * @return  \Cake\ORM\Query
     */
    public function genQueryForAllNewestVersionIDs(): \Cake\ORM\Query
    {
        /**
         * SELECT a.id FROM meta_templates a INNER JOIN (
         *   SELECT uuid, MAX(version) maxVersion FROM meta_templates GROUP BY uuid
         * ) b on a.uuid = b.uuid AND a.version = b.maxVersion;
         */
         $query = $this->find()
            ->select([
                'id'
            ])
            ->join([
                't' => [
                    'table'      => '(SELECT uuid, MAX(version) AS maxVersion FROM meta_templates GROUP BY uuid)',
                    'type'       => 'INNER',
                    'conditions' => [
                        't.uuid = MetaTemplates.uuid',
                        't.maxVersion = MetaTemplates.version'
                    ],
                ],
            ]);
        return $query;
    }

    /**
     * Get the update status of meta-templates that can be removed.
     *
     * @param array|null $updateResult
     * @return array The list of update status for new templates
     */
    public function getCanBeRemovedTemplates($updatesStatus = null): array
    {
        $updatesStatus = is_null($updatesStatus) ? $this->getUpdateStatusForTemplates() : $updatesStatus;
        foreach ($updatesStatus as $i => $updateStatus) {
            if (!$this->isRemovable($updateStatus)) {
                unset($updatesStatus[$i]);
            }
        }
        return $updatesStatus;
    }

    /**
     * Reads all template stored on the disk and parse them
     *
     * @param array|null $errors Contains errors while parsing the meta-templates
     * @return array The parsed meta-templates stored on the disk
     */
    public function readTemplatesFromDisk(&$errors = []): array
    {
        if (!is_null($this->templatesOnDisk)) {
            return $this->templatesOnDisk;
        }
        $templates = [];
        $errors = [];
        foreach (self::TEMPLATE_PATH as $path) {
            if (is_dir($path)) {
                $files = scandir($path);
                foreach ($files as $k => $file) {
                    if (substr($file, -5) === '.json') {
                        $errorMessage = '';
                        $template = $this->decodeTemplateFromDisk($path . $file, $errorMessage);
                        if (!empty($template)) {
                            $templates[] = $template;
                        } else {
                            $errors[] = $errorMessage;
                        }
                    }
                }
            }
        }
        $this->templatesOnDisk = $templates;
        return $templates;
    }

    /**
     * Read and parse the meta-template stored on disk having the provided UUID
     *
     * @param string $uuid
     * @param string $error Contains the error while parsing the meta-template
     * @return array|null The meta-template or null if not templates matche the provided UUID
     */
    public function readTemplateFromDisk(string $uuid, &$error = ''): ?array
    {
        foreach (self::TEMPLATE_PATH as $path) {
            if (is_dir($path)) {
                $files = scandir($path);
                foreach ($files as $k => $file) {
                    if (substr($file, -5) === '.json') {
                        $errorMessage = '';
                        $template = $this->decodeTemplateFromDisk($path . $file, $errorMessage);
                        if (!empty($template) && $template['uuid'] == $uuid) {
                            return $template;
                        }
                    }
                }
            }
        }
        $error = __('Could not find meta-template with UUID {0}', $uuid);
        return null;
    }

    /**
     * Read and decode the meta-template located at the provided path
     *
     * @param string $filePath
     * @param string $errorMessage
     * @return array|null The meta-template or null if there was an error while trying to decode
     */
    public function decodeTemplateFromDisk(string $filePath, &$errorMessage = ''): ?array
    {
        $file = new File($filePath, false);
        if ($file->exists()) {
            $filename = $file->name();
            $content = $file->read();
            if (empty($content)) {
                $errorMessage = __('Could not read template file `{0}`.', $filename);
                return null;
            }
            $metaTemplate = json_decode($content, true);
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
        $errorMessage = __('File does not exists');
        return null;
    }

    /**
     * Collect all enties having meta-fields belonging to the provided template
     *
     * @param integer $template_id
     * @param integer|bool $limit The limit of entities to be returned. Pass null to be ignore the limit
     * @return array List of entities
     */
    public function getEntitiesHavingMetaFieldsFromTemplate(int $metaTemplateId, $limit=10, int &$totalAmount=0): array
    {
        $metaTemplate = $this->get($metaTemplateId);
        $queryParentEntities = $this->MetaTemplateFields->MetaFields->find();
        $queryParentEntities
            ->select(['parent_id'])
            ->where([
                'meta_template_id' => $metaTemplateId
            ])
            ->group(['parent_id']);

        $entitiesTable = $this->getTableForMetaTemplateScope($metaTemplate);
        $entityQuery = $entitiesTable->find()
            ->where(['id IN' => $queryParentEntities])
            ->contain([
                'MetaFields' => [
                    'conditions' => [
                        'meta_template_id' => $metaTemplateId
                    ]
                ]
            ]);
        if (!is_null($limit)) {
            $totalAmount = $entityQuery->all()->count();
            $entityQuery->limit($limit);
        }
        $entities = $entityQuery->all()->toList();
        return $entities;
    }

    /**
     * Get the table linked to the meta-template
     *
     * @param \App\Model\Entity\MetaTemplate|string $metaTemplate
     * @return \App\Model\Table\AppTable
     */
    private function getTableForMetaTemplateScope($metaTemplateOrScope): \App\Model\Table\AppTable
    {
        if (is_string($metaTemplateOrScope)) {
            $scope = $metaTemplateOrScope;
        } else {
            $scope = $metaTemplateOrScope->scope;
        }
        $entitiesClassName = Inflector::camelize(Inflector::pluralize($scope));
        $entitiesTable = TableRegistry::getTableLocator()->get($entitiesClassName);
        return $entitiesTable;
    }

    /**
     * Get the meta-field keyed by their template_id and meta_template_id belonging to the provided entity
     *
     * @param integer $entity_id The entity for which the meta-fields belongs to
     * @param array $conditions Additional conditions to be passed to the meta-fields query
     * @return array The associated array containing the meta-fields keyed by their meta-template and meta-template-field IDs
     */
    public function getKeyedMetaFieldsForEntity(int $entity_id, array $conditions = []): array
    {
        $query = $this->MetaTemplateFields->MetaFields->find();
        $query->where(array_merge(
            $conditions,
            [
                'MetaFields.parent_id' => $entity_id
            ]
        ));
        $metaFields = $query->all();
        $keyedMetaFields = [];
        foreach ($metaFields as $metaField) {
            if (empty($keyedMetaFields[$metaField->meta_template_id][$metaField->meta_template_field_id])) {
                $keyedMetaFields[$metaField->meta_template_id][$metaField->meta_template_field_id] = [];
            }
            $keyedMetaFields[$metaField->meta_template_id][$metaField->meta_template_field_id][$metaField->id] = $metaField;
        }
        return $keyedMetaFields;
    }

    /**
     * Insert the keyed meta-fields into the provided meta-templates
     *
     * @param array $keyedMetaFields An associative array containing the meta-fields keyed by their meta-template and meta-template-field IDs
     * @param array $metaTemplates List of meta-templates
     * @return array The list of meta-template with the meta-fields inserted
     */
    public function insertMetaFieldsInMetaTemplates(array $keyedMetaFields, array $metaTemplates): array
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

    /**
     * Retreive the entity associated for the provided meta-template and id
     *
     * @param \App\Model\Entity\MetaTemplate $metaTemplate
     * @param integer $entity_id
     * @return \App\Model\Entity\AppModel
     */
    public function getEntity(\App\Model\Entity\MetaTemplate $metaTemplate, int $entity_id): \App\Model\Entity\AppModel
    {
        $entitiesTable = $this->getTableForMetaTemplateScope($metaTemplate);
        $entity = $entitiesTable->get($entity_id, [
            'contain' => 'MetaFields'
        ]);
        return $entity;
    }

    /**
     * Collect the unique default template for each scope
     *
     * @param string|null $scope
     * @return array The list of default template
     */
    public function getDefaultTemplatePerScope($scope = null): array
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

    /**
     * Remove the default flag for all meta-templates belonging to the provided scope
     *
     * @param string $scope
     * @return int the number of updated rows
     */
    public function removeDefaultFlag(string $scope): int
    {
        return $this->updateAll(
            ['is_default' => false],
            ['scope' => $scope]
        );
    }

    /**
     * Check if the provided template can be saved in the database without creating duplicate template in regards to the UUID and version
     *
     * @param array $template
     * @return boolean
     */
    public function canBeSavedWithoutDuplicates(array $template): bool
    {
        $query = $this->find()->where([
            'uuid' => $template['uuid'],
            'version' => $template['version'],
        ]);
        return $query->count() == 0;
    }

    /**
     * Create and save the provided template in the database
     *
     * @param array $template The template to be saved
     * @param array $errors The list of errors that occured during the save process
     * @param \App\Model\Entity\MetaTemplate $savedMetaTemplate The metaTemplate entity that has just been saved
     * @return boolean True if the save was successful, False otherwise
     */
    public function saveNewMetaTemplate(array $template, array &$errors = [], \App\Model\Entity\MetaTemplate &$savedMetaTemplate = null): bool
    {
        if (!$this->canBeSavedWithoutDuplicates($template)) {
            $errors[] = new UpdateError(false, __('Could not save the template. A template with this UUID and version already exists'), ['A template with UUID and version already exists']);
        }
        $template['meta_template_fields'] = $template['metaFields'];
        unset($template['metaFields']);
        $metaTemplate = $this->newEntity($template, [
            'associated' => ['MetaTemplateFields']
        ]);
        $tmp = $this->save($metaTemplate, [
            'associated' => ['MetaTemplateFields']
        ]);
        if ($tmp === false) {
            $errors[] = new UpdateError(false, __('Could not save the template.'), $metaTemplate->getErrors());
            return false;
        }
        $savedMetaTemplate = $tmp;
        return true;
    }

    /**
     * Update an existing meta-template and save it in the database
     *
     * @param \App\Model\Entity\MetaTemplate $metaTemplate The meta-template to update
     * @param array $template The template to use to update the existing meta-template
     * @param array $errors
     * @return boolean True if the save was successful, False otherwise
     */
    public function updateMetaTemplate(\App\Model\Entity\MetaTemplate $metaTemplate, array $template, array &$errors = []): bool
    {
        if (!$this->canBeSavedWithoutDuplicates($template)) {
            $errors[] = new UpdateError(false, __('Could not save the template. A template with this UUID and version already exists'), ['A template with UUID and version already exists']);
        }
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

    /**
     * Update an existing meta-template with the provided strategy and save it in the database
     *
     * @param \App\Model\Entity\MetaTemplate $metaTemplate The meta-template to update
     * @param array $template The template to use to update the existing meta-template
     * @param string $strategy The strategy to use when handling update conflicts
     * @param array $errors
     * @return boolean True if the save was successful, False otherwise
     */
    public function updateMetaTemplateWithStrategyRouter(\App\Model\Entity\MetaTemplate $metaTemplate, array $template, string $strategy, array &$errors = []): bool
    {
        if (!$this->canBeSavedWithoutDuplicates($template)) {
            $errors[] = new UpdateError(false, __('Could not save the template. A template with this UUID and version already exists'), ['A template with UUID and version already exists']);
        }
        if (is_string($metaTemplate)) {
            $errors[] = new UpdateError(false, $metaTemplate);
            return false;
        }
        if ($strategy == MetaTemplatesTable::UPDATE_STRATEGY_KEEP_BOTH) {
            $result = $this->executeStrategyKeep($template, $metaTemplate);
        } else if ($strategy == MetaTemplatesTable::UPDATE_STRATEGY_DELETE) {
            $result = $this->executeStrategyDeleteAll($template, $metaTemplate);
        } else if ($strategy == MetaTemplatesTable::UPDATE_STRATEGY_CREATE_NEW) {
            $result = $this->executeStrategyCreateNew($template, $metaTemplate);
        } else {
            $errors[] = new UpdateError(false, __('Invalid strategy {0}', $strategy));
            return false;
        }
        if (is_string($result)) {
            $errors[] = new UpdateError(false, $result);
            return false;
        }
        return true;
    }

    /**
     * Execute the `keep_both` update strategy by creating a new meta-template and moving non-conflicting entities to this one.
     * Strategy:
     * - Old template remains untouched
     * - Create new template
     * - Migrate all non-conflicting meta-fields for one entity to the new template
     * - Keep all the conflicting meta-fields for one entity on the old template
     *
     * @param array $template
     * @param \App\Model\Entity\MetaTemplate $metaTemplate
     * @return bool|string If the new template could be saved or the error message
     */
    public function executeStrategyKeep(array $template, \App\Model\Entity\MetaTemplate $metaTemplate)
    {
        if (!$this->canBeSavedWithoutDuplicates($template)) {
            $errors[] = new UpdateError(false, __('Could not save the template. A template with this UUID and version already exists'), ['A template with UUID and version already exists']);
        }
        $conflicts = $this->getMetaTemplateConflictsForMetaTemplate($metaTemplate, $template);
        $blockingConflict = Hash::extract($conflicts, '{s}.conflicts');
        $errors = [];
        if (empty($blockingConflict)) { // No conflict, everything can be updated without special care
            $this->updateMetaTemplate($metaTemplate, $template, $errors);
            return !empty($errors) ? $errors[0] : true;
        }
        $entities = $this->getEntitiesHavingMetaFieldsFromTemplate($metaTemplate->id, null);

        $conflictingEntities = [];
        foreach ($entities as $entity) {
            $conflicts = $this->getMetaFieldsConflictsUnderTemplate($entity['meta_fields'], $template);
            if (!empty($conflicts)) {
                $conflictingEntities[$entity->id] = $entity->id;
            }
        }
        if (empty($conflictingEntities)) {
            $this->updateMetaTemplate($metaTemplate, $template, $errors);
            return !empty($errors) ? $errors[0] : true;
        }
        $template['is_default'] = $metaTemplate['is_default'];
        $template['enabled'] = $metaTemplate['enabled'];
        if ($metaTemplate->is_default) {
            $metaTemplate->set('is_default', false);
            $this->save($metaTemplate);
        }
        $savedMetaTemplate = null;
        $this->saveNewMetaTemplate($template, $errors, $savedMetaTemplate);
        if (!empty($savedMetaTemplate)) {
            $savedMetaTemplateFieldByName = Hash::combine($savedMetaTemplate['meta_template_fields'], '{n}.field', '{n}');
            foreach ($entities as $entity) {
                if (empty($conflictingEntities[$entity->id])) { // conflicting entities remain untouched
                    foreach ($entity['meta_fields'] as $metaField) {
                        $savedMetaTemplateField = $savedMetaTemplateFieldByName[$metaField->field];
                        $this->supersedeMetaFieldWithMetaTemplateField($metaField, $savedMetaTemplateField);
                    }
                }
            }
        } else {
            return $errors[0]->message;
        }
        return true;
    }

    /**
     * Execute the `delete_all` update strategy by updating the meta-template and deleting all conflicting meta-fields.
     * Strategy:
     * - Delete conflicting meta-fields
     * - Update template to the new version
     *
     * @param array $template
     * @param \App\Model\Entity\MetaTemplate $metaTemplate
     * @return bool|string If the new template could be saved or the error message
     */
    public function executeStrategyDeleteAll(array $template, \App\Model\Entity\MetaTemplate $metaTemplate)
    {
        if (!$this->canBeSavedWithoutDuplicates($template)) {
            $errors[] = new UpdateError(false, __('Could not save the template. A template with this UUID and version already exists'), ['A template with UUID and version already exists']);
        }
        $errors = [];
        $conflicts = $this->getMetaTemplateConflictsForMetaTemplate($metaTemplate, $template);
        $blockingConflict = Hash::extract($conflicts, '{s}.conflicts');
        if (empty($blockingConflict)) { // No conflict, everything can be updated without special care
            $this->updateMetaTemplate($metaTemplate, $template, $errors);
            return !empty($errors) ? $errors[0] : true;
        }
        $entities = $this->getEntitiesHavingMetaFieldsFromTemplate($metaTemplate->id, null);

        foreach ($entities as $entity) {
            $conflicts = $this->getMetaFieldsConflictsUnderTemplate($entity['meta_fields'], $template);
            $deletedCount = $this->MetaTemplateFields->MetaFields->deleteAll([
                'id IN' => $conflicts
            ]);
        }
        $this->updateMetaTemplate($metaTemplate, $template, $errors);
        return !empty($errors) ? $errors[0] : true;
    }

    /**
     * Execute the `create_new` update strategy by creating a new meta-template
     * Strategy:
     * - Create a new meta-template
     * - Make the new meta-template `default` and `enabled` if previous template had these states
     * - Turn of these states on the old meta-template
     *
     * @param array $template
     * @param \App\Model\Entity\MetaTemplate $metaTemplate
     * @return bool|string If the new template could be saved or the error message
     */
    public function executeStrategyCreateNew(array $template, \App\Model\Entity\MetaTemplate $metaTemplate)
    {
        if (!$this->canBeSavedWithoutDuplicates($template)) {
            $errors[] = new UpdateError(false, __('Could not save the template. A template with this UUID and version already exists'), ['A template with UUID and version already exists']);
        }
        $errors = [];
        $template['is_default'] = $metaTemplate->is_default;
        $template['enabled'] = $metaTemplate->enabled;
        $savedMetaTemplate = null;
        $success = $this->saveNewMetaTemplate($template, $errors, $savedMetaTemplate);
        if ($success) {
            if ($metaTemplate->is_default) {
                $metaTemplate->set('is_default', false);
                $metaTemplate->set('enabled', false);
                $this->save($metaTemplate);
            }
        }
        return !empty($errors) ? $errors[0] : true;
    }

    /**
     * Supersede a meta-fields's meta-template-field with the provided one.
     *
     * @param \App\Model\Entity\MetaField $metaField
     * @param \App\Model\Entity\MetaTemplateField $savedMetaTemplateField
     * @return bool True if the replacement was a success, False otherwise
     */
    public function supersedeMetaFieldWithMetaTemplateField(\App\Model\Entity\MetaField $metaField,  \App\Model\Entity\MetaTemplateField $savedMetaTemplateField): bool
    {
        $metaField->set('meta_template_id', $savedMetaTemplateField->meta_template_id);
        $metaField->set('meta_template_field_id', $savedMetaTemplateField->id);
        $metaField = $this->MetaTemplateFields->MetaFields->save($metaField);
        return !empty($metaField);
    }

    /**
     * Compute the validity of the provided meta-fields under the provided meta-template
     *
     * @param \App\Model\Entity\MetaField[] $metaFields
     * @param array|\App\Model\Entity\MetaTemplate $template
     * @return \App\Model\Entity\MetaField[] The list of conflicting meta-fields under the provided template
     */
    public function getMetaFieldsConflictsUnderTemplate(array $metaFields, $template): array
    {
        if (!is_array($template) && get_class($template) == 'App\Model\Entity\MetaTemplate') {
            $metaTemplateFields = $template->meta_template_fields;
            $existingMetaTemplate = true;
        } else {
            $metaTemplateFields = $template['metaFields'];
        }
        $conflicting = [];
        $metaTemplateFieldByName = [];
        foreach ($metaTemplateFields as $metaTemplateField) {
            if (!is_array($template)) {
                $metaTemplateField = $metaTemplateField->toArray();
            }
            $metaTemplateFieldByName[$metaTemplateField['field']] = $this->MetaTemplateFields->newEntity($metaTemplateField);
        }
        foreach ($metaFields as $metaField) {
            if ($existingMetaTemplate && $metaField->meta_template_id != $template->id) {
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

    /**
     * Compute the potential conflict that would be introduced by updating an existing meta-template-field with the provided one.
     * This will go through all instanciation of the existing meta-template-field and checking their validity against the provided one.
     *
     * @param \App\Model\Entity\MetaTemplateField $metaTemplateField
     * @param array $templateField
     * @return array
     */
    public function computeExistingMetaTemplateFieldConflictForMetaTemplateField(\App\Model\Entity\MetaTemplateField $metaTemplateField, array $templateField): array
    {
        $result = [
            'automatically-updateable' => true,
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
                $result['automatically-updateable'] = false;
                $result['conflicts'][] = __('This field is no longer multiple and is being that way');
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
                $entitiesTable = $this->getTableForMetaTemplateScope($entitiesWithMetaField[0]['scope']);
                $entities = $entitiesTable->find()
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
                    $result['automatically-updateable'] = $result['automatically-updateable'] && false;
                    $result['conflicts'][] = __('This field is instantiated with values not passing the validation anymore');
                    $result['conflictingEntities'] = $conflictingEntities;
                }
            }
        }
        return $result;
    }

    /**
     * Check the conflict that would be introduced if the metaTemplate would be updated to the provided template
     *
     * @param \App\Model\Entity\MetaTemplate $metaTemplate
     * @param \App\Model\Entity\MetaTemplate|array $template
     * @return array
     */
    public function getMetaTemplateConflictsForMetaTemplate(\App\Model\Entity\MetaTemplate $metaTemplate, $template): array
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
                    $metaFieldArray = !is_array($newMetaField) && get_class($newMetaField) == 'App\Model\Entity\MetaTemplateField' ? $newMetaField->toArray() : $newMetaField;
                    $templateConflictsForMetaField = $this->computeExistingMetaTemplateFieldConflictForMetaTemplateField($metaField, $metaFieldArray);
                    if (!$templateConflictsForMetaField['automatically-updateable']) {
                        $conflicts[$metaField->field] = $templateConflictsForMetaField;
                        $conflicts[$metaField->field]['existing_meta_template_field'] = $metaField;
                        $conflicts[$metaField->field]['existing_meta_template_field']['conflicts'] = $templateConflictsForMetaField['conflicts'];
                    }
                }
            }
        }
        if (!empty($existingMetaTemplateFields)) {
            foreach ($existingMetaTemplateFields as $field => $tmp) {
                $conflicts[$field] = [
                    'automatically-updateable' => false,
                    'conflicts' => [__('This field is intended to be removed')],
                ];
            }
        }
        return $conflicts;
    }

    /**
     * Get update status for the latest meta-template in the database for the provided template
     *
     * @param array $template
     * @param \App\Model\Entity\MetaTemplate $metaTemplate $metaTemplate
     * @return array 
     */
    public function getUpdateStatusForTemplate(array $template): array
    {
        $updateStatus = [
            'new' => true,
            'up-to-date' => false,
            'automatically-updateable' => false,
            'conflicts' => [],
            'template' => $template,
        ];
        $query = $this->find()
            ->contain('MetaTemplateFields')
            ->where([
                'uuid' => $template['uuid'],
            ])
            ->order(['version' => 'DESC']);
        $metaTemplate = $query->first();
        if (!empty($metaTemplate)) {
            $updateStatus = array_merge(
                $updateStatus,
                $this->getStatusForMetaTemplate($template, $metaTemplate)
            );
        }
        return $updateStatus;
    }

    /**
     * Get update status for the meta-template stored in the database and the provided template
     *
     * @param array $template
     * @param \App\Model\Entity\MetaTemplate $metaTemplate
     * @return array
     */
    public function getStatusForMetaTemplate(array $template, \App\Model\Entity\MetaTemplate $metaTemplate): array
    {
        $updateStatus = [];
        $updateStatus['existing_template'] = $metaTemplate;
        $updateStatus['current_version'] = $metaTemplate->version;
        $updateStatus['next_version'] = $template['version'];
        $updateStatus['new'] = false;
        if ($metaTemplate->version >= $template['version']) {
            $updateStatus['up-to-date'] = true;
            $updateStatus['automatically-updateable'] = false;
            $updateStatus['conflicts'][] = __('Could not update the template. Local version is equal or newer.');
            return $updateStatus;
        }

        $conflicts = $this->getMetaTemplateConflictsForMetaTemplate($metaTemplate, $template);
        if (!empty($conflicts)) {
            $updateStatus['conflicts'] = $conflicts;
        } else {
            $updateStatus['automatically-updateable'] = true;
        }
        $updateStatus['meta_field_amount'] = $this->MetaTemplateFields->MetaFields->find()->where(['meta_template_id' => $metaTemplate->id])->count();
        $updateStatus['can-be-removed'] = empty($updateStatus['meta_field_amount']) && empty($updateStatus['to-existing']);
        return $updateStatus;
    }

    /**
     * Massages the meta-fields of an entity based on the input
     * - If the keyed ID of the input meta-field is new, a new meta-field entity is created
     * - If the input meta-field's value is empty for an existing meta-field, the existing meta-field is marked as to be deleted
     * - If the input meta-field already exists, patch the entity and attach the validation errors
     *
     * @param \App\Model\Entity\AppModel $entity
     * @param array $input
     * @param \App\Model\Entity\MetaTemplate $metaTemplate
     * @return array An array containing the entity with its massaged meta-fields and the meta-fields that should be deleted
     */
    public function massageMetaFieldsBeforeSave(\App\Model\Entity\AppModel $entity, array $input, \App\Model\Entity\MetaTemplate $metaTemplate): array
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
                            } else { // metafield comes from a second POST where the temporary entity has already been created
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

    public function __construct($success = false, $message = '', $errors = [])
    {
        $this->success = $success;
        $this->message = $message;
        $this->errors = $errors;
    }
}
