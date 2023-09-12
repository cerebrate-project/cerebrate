<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Error\Debugger;
use App\Model\Entity\User;

class OrgGroupsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->addBehavior('Timestamp');
        $this->addBehavior('Tags.Tag');
        $this->addBehavior('AuditLog');
        $this->belongsToMany('Organisations', [
            'joinTable' => 'org_groups_organisations',
        ]);
        $this->belongsToMany('Users', [
            'joinTable' => 'org_groups_admins',
        ]);
        $this->setDisplayField('name');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('name')
            ->notEmptyString('uuid')
            ->requirePresence(['name', 'uuid'], 'create');
        return $validator;
    }
    
    public function checkIfGroupAdmin(int $groupId, User $user): bool
    {
        $orgGroup = $this->get($groupId, ['contain' => 'Users']);
        if (empty($orgGroup)) {
            return false;
        }
        foreach ($orgGroup['users'] as $u) {
            if ($user['id'] == $u['id']) {
                return true;
            }
        }
        return false;
    }

    public function checkIfUserBelongsToGroupAdminsGroup(User $currentUser, User $userToCheck): bool
    {
        $managedGroups = $this->find('list')->where(['Users.id' => $currentUser['id']])->select(['id', 'uuid'])->disableHydration()->toArray();
        return isset($managedGroups[$userToCheck['org_id']]);
    }
}
