<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class MetaTemplatesTable extends AppTable
{
    public $metaFields = true;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->hasMany(
            'MetaTemplateFields',
            [
                'foreignKey' => 'meta_template_id'
            ]
        );
        $this->setDisplayField('field');
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
                        if ($this->loadMetaFile($path . $file) === true) {
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

    public function loadMetaFile(String $filePath)
    {
        if (file_exists($filePath)) {
            $contents = file_get_contents($filePath);
            $metaTemplate = json_decode($contents, true);
            if (!empty($metaTemplate) && !empty($metaTemplate['uuid']) && !empty($metaTemplate['version'])) {
                $query = $this->find();
                $query->where(['uuid' => $metaTemplate['uuid']]);
                $template = $query->first();
                if (empty($template)) {
                    $template = $this->newEntity($metaTemplate);
                    $result = $this->save($template);
                    if (!$result) {
                        return __('Something went wrong, could not create the template.');
                    }
                } else {
                    if ($template->version >= $metaTemplate['version']) {
                        return false;
                    }
                    foreach (['version', 'source', 'name', 'namespace', 'scope', 'description'] as $field) {
                        $template->{$field} = $metaTemplate[$field];
                    }
                    $result = $this->save($template);
                    if (!$result) {
                        return __('Something went wrong, could not update the template.');
                        return false;
                    }
                }
                if ($result) {
                    $this->MetaTemplateFields->deleteAll(['meta_template_id' => $template->id]);
                    foreach ($metaTemplate['metaFields'] as $metaField) {
                        $metaField['meta_template_id'] = $template->id;
                        $metaField = $this->MetaTemplateFields->newEntity($metaField);
                        $this->MetaTemplateFields->save($metaField);
                    }

                }
            }
            return true;
        }

    }
}
