<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;

class SharingGroupsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->belongsTo(
            'Users'
        );
        $this->belongsTo(
            'Organisations'
        );
        $this->belongsToMany(
            'SharingGroupOrgs',
            [
                'className' => 'Organisations',
                'foreignKey' => 'sharing_group_id',
                'joinTable' => 'sgo',
                'targetForeignKey' => 'organisation_id'
            ]
        );
        $this->setDisplayField('name');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence(['name', 'releasability'], 'create');
        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules;
    }
}
