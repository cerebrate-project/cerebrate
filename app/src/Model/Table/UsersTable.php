<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\RulesChecker;

class UsersTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->belongsTo(
            'Individuals',
            [
                'dependent' => false,
                'cascadeCallbacks' => false
            ]
        );
        $this->belongsTo(
            'Roles',
            [
                'dependent' => false,
                'cascadeCallbacks' => false
            ]
        );
        $this->setDisplayField('username');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence(['password'], 'create');
        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules;
    }
}
