<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class EnumerationsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo(
            'EnumerationCollection'
        );
        $this->setDisplayField('value');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('value')
            ->requirePresence(['value'], 'create')
            ->notEmptyString('enumeration_collection_id')
            ->requirePresence(['enumeration_collection_id'], 'create');
        return $validator;
    }
}
