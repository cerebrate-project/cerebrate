<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class MetaTemplatesTable extends AppTable
{
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

    public function update()
    {
        $paths = [
            ROOT . '/libraries/default/meta_fields/',
            ROOT . '/libraries/custom/meta_fields/'
        ];
        $files_processed = [];
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $files = scandir($path);
                foreach ($files as $k => $file) {
                    if (substr($file, -5) === '.json') {
                        if ($this->loadAndSaveMetaFile($path . $file) === true) {
                            $files_processed[] = $file;
                        }
                    }
                }
            }
        }
        return $files_processed;
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
        $query->contain('MetaTemaplteFields')->where(['uuid' => $newMetaTemplate['uuid']]);
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
        if (false) { // Field has been removed
        }
        if (false) { // Field no longer multiple
        }
        if (false) { // Field no longer pass validation
        }
        return true;
    }
}
