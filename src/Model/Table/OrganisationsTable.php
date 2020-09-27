<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class OrganisationsTable extends AppTable
{
    public $metaFields = 'organisation';

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->hasMany(
            'Alignments',
            [
                'dependent' => true,
                'cascadeCallbacks' => true
            ]
        );
        $this->hasMany(
            'EncryptionKeys',
            [
                'foreignKey' => 'owner_id',
                'conditions' => ['owner_type' => 'organisation']
            ]
        );
        $this->hasMany(
            'MetaFields',
            [
                'foreignKey' => 'parent_id',
                'conditions' => ['scope' => 'organisation']
            ]
        );
        $this->setDisplayField('name');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('name')
            ->notEmptyString('uuid')
            ->requirePresence(['name', 'uuid'], 'create');
        return $validator;
    }
}
