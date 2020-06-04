<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class EncryptionKeysTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo(
            'Individuals',
            [
                'foreignKey' => 'owner_id',
                'conditions' => ['owner_type' => 'individual']
            ]
        );
        $this->belongsTo(
            'Organisations',
            [
                'foreignKey' => 'owner_id',
                'conditions' => ['owner_type' => 'organisation']
            ]
        );
        $this->setDisplayField('encryption_key');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('type')
            ->notEmptyString('encryption_key')
            ->notEmptyString('uuid')
            ->notEmptyString('owner_id')
            ->notEmptyString('owner_type')
            ->requirePresence(['type', 'encryption_key', 'uuid', 'owner_id', 'owner_type'], 'create');
        return $validator;
    }
}
