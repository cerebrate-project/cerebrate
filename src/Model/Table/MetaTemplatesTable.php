<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Utility\Hash;

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
                'foreignKey' => 'meta_template_id'
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

    public function update(&$errors=[])
    {
        $files_processed = [];
        // foreach (self::TEMPLATE_PATH as $path) {
        //     if (is_dir($path)) {
        //         $files = scandir($path);
        //         foreach ($files as $k => $file) {
        //             if (substr($file, -5) === '.json') {
        //                 if ($this->loadAndSaveMetaFile($path . $file) === true) {
        //                     $files_processed[] = $file;
        //                 }
        //             }
        //         }
        //     }
        // }
        $readErrors = [];
        $preUpdateChecks = [];
        $updatesErrors = [];
        $templates = $this->readTemplatesFromDisk($readErrors);
        foreach ($templates as $template) {
            $preUpdateChecks[$template['uuid']] = $this->checkForUpdates($template);
        }
        $errors = [
            'read_errors' => $readErrors,
            'pre_update_errors' => $preUpdateChecks,
            'update_errors' => $updatesErrors,
        ];
        return $files_processed;
    }

    public function checkForUpdates(): array
    {
        $templates = $this->readTemplatesFromDisk($readErrors);
        $result = [];
        foreach ($templates as $template) {
            $result[$template['uuid']] = $this->checkUpdatesForTemplate($template);
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

    public function getTemplateStatus(array $updateResult): array
    {
        return [
            'up_to_date' => $this->isUpToDate($updateResult),
            'updateable' => $this->isUpdateable($updateResult),
            'is_new' => $this->isNew($updateResult),
            'has_conflict' => $this->hasConflict($updateResult),
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

    public function loadAndSaveMetaFile(String $filePath)
    {
        if (file_exists($filePath)) {
            $contents = file_get_contents($filePath);
            $metaTemplate = json_decode($contents, true);
            if (empty($metaTemplate)) {
                return __('Could not load template file. Error while decoding the template\'s JSON');
            }
            if (empty($metaTemplate['uuid']) || empty($metaTemplate['version'])) {
                return __('Could not load template file. Invalid template file. Missing template UUID or version');
            }
            return $this->saveMetaFile($metaTemplate);
        }
        return __('Could not load template file. File does not exists');
    }

    public function saveMetaFile(array $newMetaTemplate)
    {
        $query = $this->find();
        $query->contain('MetaTemplateFields')->where(['uuid' => $newMetaTemplate['uuid']]);
        $metaTemplate = $query->first();
        if (empty($metaTemplate)) {
            $metaTemplate = $this->newEntity($newMetaTemplate);
            $result = $this->save($metaTemplate);
            if (!$result) {
                return __('Something went wrong, could not create the template.');
            }
        } else {
            if ($metaTemplate->version >= $newMetaTemplate['version']) {
                return __('Could not update the template. Local version is newer.');
            }
            // Take care of meta template fields
            $metaTemplate = $this->patchEntity($metaTemplate, $newMetaTemplate);
            $metaTemplate = $this->save($metaTemplate);
            if (!$metaTemplate) {
                return __('Something went wrong, could not update the template.');
            }
        }
        if ($result) {
            $this->MetaTemplateFields->deleteAll(['meta_template_id' => $template->id]);
            foreach ($newMetaTemplate['metaFields'] as $metaField) {
                $metaField['meta_template_id'] = $template->id;
                $metaField = $this->MetaTemplateFields->newEntity($metaField);
                $this->MetaTemplateFields->save($metaField);
            }
        }
    }

    public function handleMetaTemplateFieldUpdateEdgeCase($metaTemplateField, $newMetaTemplateField)
    {
    }

    public function checkForMetaFieldConflicts(\App\Model\Entity\MetaTemplateField $metaField, array $templateField): array
    {
        $result = [
            'updateable' => true,
            'conflicts' => [],
        ];
        if ($metaField->multiple && $templateField['multiple'] == false) { // Field is no longer multiple
            $result['updateable'] = false;
            $result['conflicts'][] = __('This field is no longer multiple');
        }
        if (!empty($templateField['regex']) && $templateField['regex'] != $metaField->regex) {
             // FIXME: Check if all meta-fields pass the new validation
            $result['updateable'] = false;
            $result['conflicts'][] = __('This field is instantiated with values not passing the validation anymore');
        }
        return $result;
    }

    public function checkForMetaTemplateConflicts(\App\Model\Entity\MetaTemplate $metaTemplate, array $template): array
    {
        $conflicts = [];
        $existingMetaTemplateFields = Hash::combine($metaTemplate->toArray(), 'meta_template_fields.{n}.field');
        foreach ($template['metaFields'] as $newMetaField) {
            foreach ($metaTemplate->meta_template_fields as $metaField) {
                if ($newMetaField['field'] == $metaField->field) {
                    unset($existingMetaTemplateFields[$metaField->field]);
                    $templateConflicts = $this->checkForMetaFieldConflicts($metaField, $newMetaField);
                    if (!$templateConflicts['updateable']) {
                        $conflicts[$metaField->field] = $templateConflicts;
                    }
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

    public function checkUpdatesForTemplate($template): array
    {
        $result = [
            'new' => true,
            'up-to-date' => false,
            'updateable' => false,
            'conflicts' => [],
            'template' => $template,
        ];
        $query = $this->find()
            ->contain('MetaTemplateFields')->where([
                'uuid' => $template['uuid']
            ]);
        $metaTemplate = $query->first();
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
}
