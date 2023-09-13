<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Error\Debugger;
use App\Model\Entity\User;
use Cake\Utility\Hash;

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
        if (!empty($user['role']['perm_admin'])) {
            return true;
        }
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
        $managedGroups = $this->find('all')
            ->matching(
                'Users',
                function ($q) use ($currentUser) {
                    return $q->where(
                        [
                            'Users.id' => $currentUser['id']
                        ]
                    );
                }
            )
            ->contain(['Organisations'])
            ->toArray();
        $org_ids = Hash::extract($managedGroups, '{n}.organisations.{n}.id');
        return in_array($userToCheck['organisation_id'], $org_ids);
    }

    public function getGroupOrgIdsForUser(User $user): array
    {
        $managedGroups = $this->find('all')
            ->matching(
                'Users',
                function ($q) use ($user) {
                    return $q->where(
                        [
                            'Users.id' => $user['id']
                        ]
                    );
                }
            )
            ->contain(['Organisations'])
            ->toArray();
        return array_unique(Hash::extract($managedGroups, '{n}.organisations.{n}.id'));
    }
}
