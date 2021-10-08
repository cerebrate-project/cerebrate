<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class UserSettingsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('Timestamp');
        $this->belongsTo(
            'Users'
        );
        $this->setDisplayField('name');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence(['name', 'user_id'], 'create')
            ->notEmptyString('name', __('Please fill this field'))
            ->notEmptyString('user_id', __('Please supply the user id to which this setting belongs to'));
        return $validator;
    }
}
