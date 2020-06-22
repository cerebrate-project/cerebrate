<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;

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

    public function checkForNewInstance(): bool
    {
        if (empty($this->find()->first())) {
            $this->Roles = TableRegistry::get('Roles');
            $role = $this->Roles->newEntity([
                'name' => 'admin',
                'perm_admin' => 1
            ]);
            $this->Roles->save($role);
            $this->Individuals = TableRegistry::get('Individuals');
            $individual = $this->Individuals->newEntity([
                'email' => 'admin@admin.test',
                'first_name' => 'admin',
                'last_name' => 'admin'
            ]);
            $this->Individuals->save($individual);
            $user = $this->newEntity([
                'username' => 'admin',
                'password' => 'Password1234',
                'individual_id' => $individual->id,
                'role_id' => $role->id
            ]);
            $this->save($user);
        }
        return true;
    }
}
