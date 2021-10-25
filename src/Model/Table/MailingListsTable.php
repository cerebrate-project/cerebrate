<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;

class MailingListsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->addBehavior('Timestamp');
        $this->belongsTo(
            'Users'
        );
        // $this->belongsToMany(
        //     'Individuals',
        //     [
        //         'className' => 'Individuals',
        //         'foreignKey' => 'individual_id',
        //         'joinTable' => 'sgo',
        //         'targetForeignKey' => 'organisation_id'
        //     ]
        // );

        $this->belongsToMany('Individuals', [
            'joinTable' => 'mailing_lists_individuals',
        ]);

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