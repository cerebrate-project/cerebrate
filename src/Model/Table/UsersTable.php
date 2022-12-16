<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Datasource\EntityInterface;
use Cake\Http\Session;
use Cake\Http\Client;
use Cake\Utility\Security;
use Cake\Core\Configure;
use Cake\Utility\Text;
use ArrayObject;

class UsersTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('Timestamp');
        $this->addBehavior('UUID');
        $this->addBehavior('MetaFields');
        $this->addBehavior('AuditLog');
        $this->addBehavior('NotifyAdmins', [
            'fields' => ['role_id', 'individual_id', 'organisation_id', 'disabled', 'modified', 'meta_fields'],
        ]);
        $this->initAuthBehaviors();
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
        $this->belongsTo(
            'Organisations',
            [
                'dependent' => false,
                'cascadeCallbacks' => false
            ]
        );
        $this->hasMany(
            'UserSettings',
            [
                'dependent' => true,
                'cascadeCallbacks' => true
            ]
        );
        $this->setDisplayField('username');
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['username'])) {
            $data['username'] = trim(mb_strtolower($data['username']));
        }
    }

    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options)
    {
        $success = true;
        if (!$entity->isNew()) {
            $success = $this->handleUserUpdateRouter($entity);
        }
        $permissionRestrictionCheck = $this->checkPermissionRestrictions($entity);
        if ($permissionRestrictionCheck !== true) {
            $entity->setErrors($permissionRestrictionCheck);
            $event->stopPropagation();
            $event->setResult(false);
            return false;
        }
        return $success;
    }

    private function checkPermissionRestrictions(EntityInterface $entity)
    {
        if (!isset($this->PermissionLimitations)) {
            $this->PermissionLimitations = TableRegistry::get('PermissionLimitations');
        }
        $permissions = $this->PermissionLimitations->getListOfLimitations($entity);
        foreach ($permissions as $permission_name => $permission) {
            foreach ($permission as $scope => $permission_data) {
                $valueToCompareTo = $permission_data['current'];

                $enabled = false;
                if (!empty($entity->meta_fields)) {
                    foreach ($entity['meta_fields'] as $metaField) {
                        if ($metaField['field'] === $permission_name) {
                            $enabled = true;
                            if ($metaField->isNew()) {
                                $valueToCompareTo += !empty($metaField->value) ? 1 : 0;
                            } else {
                                $valueToCompareTo += !empty($metaField->value) ? 0 : -1;
                            }
                        }
                    }
                }

                if (!$enabled && !empty($entity->_metafields_to_delete)) {
                    foreach ($entity->_metafields_to_delete as $metaFieldToDelete) {
                        if ($metaFieldToDelete['field'] === $permission_name) {
                            $valueToCompareTo += !empty($metaFieldToDelete->value) ? -1 : 0;
                        }
                    }
                }

                if ($valueToCompareTo > $permission_data['limit']) {
                    return [
                        $permission_name => 
                        __(
                            '{0} limit exceeded.',
                            $scope
                        )
                    ];
                }
            }
        }
        return true;
    }

    private function initAuthBehaviors()
    {
        if (!empty(Configure::read('keycloak'))) {
            $this->addBehavior('AuthKeycloak');
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->setStopOnFailure()
            ->requirePresence(['password'], 'create')
            ->add('password', [
                'password_complexity' => [
                    'rule' => function($value, $context) {
                        if (!preg_match('/^((?=.*\d)|(?=.*\W+))(?![\n])(?=.*[A-Z])(?=.*[a-z]).*$|.{16,}/s', $value) || strlen($value) < 12) {
                            return false;
                        }
                        return true;
                    },
                    'message' => __('Invalid password. Passwords have to be either 16 character long or 12 character long with 3/4 special groups.')
                ],
                'password_confirmation' => [
                    'rule' => function($value, $context) {
                        if (isset($context['data']['confirm_password'])) {
                            if ($context['data']['confirm_password'] !== $value) {
                                return false;
                            }
                        }
                        return true;
                    },
                    'message' => __('Password confirmation missing or not matching the password.')
                ]
            ])
            ->add('username', [
                'username_policy' => [
                    'rule' => function($value, $context) {
                        if (mb_strlen(trim($value)) < 5 || mb_strlen(trim($value)) > 50) {
                            return __('Invalid username length. Make sure that you provide a username of at least 5 and up to 50 characters in length.');
                        }
                        return true;
                    }
                ]
            ])
            ->requirePresence(['username'], 'create')
            ->notEmptyString('username', __('Please fill this field'), 'create');
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
                'perm_admin' => 1,
                'perm_org_admin' => 1,
                'perm_sync' => 1
            ]);
            $this->Roles->save($role);
            $this->Organisations = TableRegistry::get('Organisations');
            $organisation = $this->Organisations->newEntity([
                'name' => 'default_organisation',
                'uuid' => Text::uuid()
            ]);
            $this->Organisations->save($organisation);
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
                'organisation_id' => $organisation->id,
                'role_id' => $role->id
            ]);
            $this->save($user);
        }
        return true;
    }

    public function captureIndividual($user): int
    {
        $individual = $this->Individuals->find()->where(['email' => $user['individual']['email']])->first();
        if (empty($individual)) {
            $individual = $this->Individuals->newEntity($user['individual']);
            if (!$this->Individuals->save($individual)) {
                throw new BadRequestException(__('Could not save the associated individual'));
            }
        }
        return $individual->id;
    }

    public function captureOrganisation($user): int
    {
        $organisation = $this->Organisations->find()->where(['uuid' => $user['organisation']['uuid']])->first();
        if (empty($organisation)) {
            $user['organisation']['name'] = $user['organisation']['uuid'];
            $organisation = $this->Organisations->newEntity($user['organisation']);
            if (!$this->Organisations->save($organisation)) {
                throw new BadRequestException(__('Could not save the associated organisation'));
            }
        }
        return $organisation->id;
    }

    public function captureRole($user): int
    {
        $role = $this->Roles->find()->where(['name' => $user['role']['name']])->first();
        if (empty($role)) {
            if (empty($role)) {
                throw new NotFoundException(__('Invalid role'));
            }
        }
        return $role->id;
    }

    public function enrollUserRouter($data): void
    {
        if (!empty(Configure::read('keycloak'))) {
            $this->enrollUser($data);
        }
    }

    public function handleUserUpdateRouter(\App\Model\Entity\User $user): bool
    {
        if (!empty(Configure::read('keycloak.enabled'))) {
            $success = $this->handleUserUpdate($user);
            // return $success;
        }
        return true;
    }
}
