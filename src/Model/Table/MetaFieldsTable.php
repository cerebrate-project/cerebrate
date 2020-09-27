<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class MetaFieldsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->setDisplayField('field');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('scope')
            ->notEmptyString('field')
            ->notEmptyString('uuid')
            ->notEmptyString('value')
            ->requirePresence(['scope', 'field', 'value', 'uuid'], 'create');
        return $validator;
    }
}
