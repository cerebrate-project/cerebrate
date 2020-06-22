<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class AlignmentsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('Individuals');
        $this->belongsTo('Organisations');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('individual_id')
            ->notEmptyString('organisation_id')
            ->requirePresence(['individual_id', 'organisation_id'], 'create');
        return $validator;
    }
}
