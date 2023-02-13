<?php

namespace App\Model\Table;

use App\Model\Entity\MetaTemplate;
use App\Model\Entity\MetaTemplateNameDirectory;
use App\Model\Table\AppTable;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

class MetaTemplateNameDirectoryTable extends AppTable
{

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->hasMany(
            'MetaFields',
            [
                'foreignKey' => 'meta_template_directory_id',
            ]
        );
        $this->setDisplayField('name');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('name')
            ->notEmptyString('namespace')
            ->notEmptyString('uuid')
            ->notEmptyString('version')
            ->requirePresence(['version', 'uuid', 'name', 'namespace'], 'create');
        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(
            ['uuid', 'version'],
            __('This meta-template already exists.')
        ));
        return $rules;
    }

    public function createFromMetaTemplate(MetaTemplate $metaTemplate): MetaTemplateNameDirectory
    {
        $metaTemplateDirectory = $this->newEntity([
            'name' => $metaTemplate['name'],
            'namespace' => $metaTemplate['namespace'],
            'uuid' => $metaTemplate['uuid'],
            'version' => $metaTemplate['version'],
        ]);
        $existingTemplate = $this->find()
            ->where([
                'uuid' => $metaTemplate['uuid'],
                'version' => $metaTemplate['version'],
            ])->first();
        if (!empty($existingTemplate)) {
            return $existingTemplate;
        }
        $this->save($metaTemplateDirectory);
        return $metaTemplateDirectory;
    }
}
