<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class IndividualsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->hasMany('Alignments');
        $this->hasMany(
            'EncryptionKeys',
            [
                'foreignKey' => 'owner_id',
                'conditions' => ['owner_type' => 'individual']
            ]
        );
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('email')
            ->notEmptyString('uuid')
            ->requirePresence(['email', 'uuid'], 'create');
        return $validator;
    }
}
