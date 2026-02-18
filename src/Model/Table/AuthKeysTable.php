<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Utility\Security;
use Cake\Http\Exception\MethodNotAllowedException;
use ArrayObject;

class AuthKeysTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->addBehavior('AuditLog');
        $this->belongsTo(
            'Users'
        );
        $this->setDisplayField('comment');
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        $data['created'] = time();
        if (!isset($data['expiration']) || empty($data['expiration'])) {
            $data['expiration'] = 0;
        } else {
            $data['expiration'] = strtotime($data['expiration']);
        }
    }

    public function beforeSave(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        if (empty($entity->authkey)) {
            $authkey = $this->generateAuthKey();
            if (empty($entity->created)) {
                $entity->created = time();
            }
            $entity->authkey_start = substr($authkey, 0, 4);
            $entity->authkey_end = substr($authkey, -4);
            $entity->authkey = (new DefaultPasswordHasher())->hash($authkey);
            $entity->authkey_raw = $authkey;
        }
    }

    public function generateAuthKey()
    {
        return Security::randomString(40);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('user_id')
            ->requirePresence(['user_id'], 'create')
            ->add('expiration', 'custom', [
                'rule' => function ($value, $context) {
                    if ($value && $value < time()) {
                        return false;
                    }
                    return true;
                },
                'message' => __('Expiration date/time has to be in the future.')
            ]);
        return $validator;
    }

    public function checkKey($authkey)
    {
        if (strlen($authkey) != 40) {
            return [];
        }
        $start = substr($authkey, 0, 4);
        $end = substr($authkey, -4);
        $candidates = $this->find()->where([
            'authkey_start' => $start,
            'authkey_end' => $end,
            'OR' => [
                'expiration' => 0,
                'expiration >' => time()
            ]
        ]);
        if (!empty($candidates)) {
            foreach ($candidates as $candidate) {
                if ((new DefaultPasswordHasher())->check($authkey, $candidate['authkey'])) {
                    return $candidate;
                }
            }
        }
        return [];
    }

    public function buildUserConditions($currentUser)
    {
        $conditions = [];
        $validOrgs = $this->Users->getValidOrgsForUser($currentUser);
        if (empty($currentUser['role']['perm_community_admin'])) {
            $conditions['Users.organisation_id IN'] = $validOrgs;
            if (empty($currentUser['role']['perm_group_admin'])) {
                if (empty($currentUser['role']['perm_org_admin'])) {
                    $conditions['Users.id'] = $currentUser['id'];
                } else {
                    $role_ids = $this->Users->Roles->find()->where(['perm_admin' => 0, 'perm_community_admin' => 0, 'perm_org_admin' => 0, 'perm_group_admin' => 0])->all()->extract('id')->toList();
                    $conditions['Users.organisation_id'] = $currentUser['organisation_id'];
                    $subConditions = [
                        ['Users.id' => $currentUser['id']]
                    ];
                    if (!empty($role_ids)) {
                        $subConditions[] = ['Users.role_id IN' => $role_ids];
                    }
                    $conditions['OR'] = $subConditions;
                }
            } else {
                $conditions['Users.group_id'] = $currentUser['group_id'];
                $role_ids = $this->Users->Roles->find()->where(['perm_admin' => 0, 'perm_community_admin' => 0, 'perm_group_admin' => 0])->all()->extract('id')->toList();
                $conditions['OR'] = [
                    ['Users.id' => $currentUser['id']],
                    ['Users.role_id IN' => $role_ids]
                ];
            }
        }
        return $conditions;
    }
}
