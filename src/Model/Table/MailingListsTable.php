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

        $this->belongsToMany('Individuals', [
            'joinTable' => 'mailing_lists_individuals',
        ]);
        // Change to HasMany?
        $this->belongsToMany('MetaFields');

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

    public function isIndividualListed($individual, \App\Model\Entity\MailingList $mailinglist): bool
    {
        $found = false;
        if (empty($mailinglist['individuals'])) {
            return false;
        }
        $individual_id_to_find = $individual;
        if (is_object($individual)) {
            $individual_id_to_find = $individual['id'];
        }
        foreach ($mailinglist['individuals'] as $individual) {
            if ($individual['id'] == $individual_id_to_find) {
                return true;
            }
        }
        return $found;
    }
}