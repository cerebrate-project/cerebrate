<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;
use \Cake\Datasource\EntityInterface;
use \Cake\Http\Session;
use Cake\Http\Client;
use Cake\Utility\Security;
use Cake\Core\Configure;

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
            ->requirePresence(['username'], 'create')
            ->notEmptyString('username', 'Please fill this field');
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

    private function extractKeycloakProfileData($profile_payload)
    {
        $mapping = Configure::read('keycloak.mapping');
        $fields = [
            'org_uuid' => 'org_uuid',
            'role_name' => 'role_name',
            'username' => 'preferred_username',
            'email' => 'email',
            'first_name' => 'given_name',
            'last_name' => 'family_name'
        ];
        foreach ($fields as $field => $default) {
            if (!empty($mapping[$field])) {
                $fields[$field] = $mapping[$field];
            }
        }
        $user = [
            'individual' => [
                'email' => $profile_payload[$fields['email']],
                'first_name' => $profile_payload[$fields['first_name']],
                'last_name' => $profile_payload[$fields['last_name']]
            ],
            'user' => [
                'username' => $profile_payload[$fields['username']],
            ],
            'organisation' => [
                'uuid' => $profile_payload[$fields['org_uuid']],
            ],
            'role' => [
                'name' => $profile_payload[$fields['role_name']],
            ]
        ];
        $user['user']['individual_id'] = $this->captureIndividual($user);
        $user['user']['role_id'] = $this->captureRole($user);
        $existingUser = $this->find()->where(['username' => $user['user']['username']])->first();
        if (empty($existingUser)) {
            $user['user']['password'] = Security::randomString(16);
            $existingUser = $this->newEntity($user['user']);
            if (!$this->save($existingUser)) {
                return false;
            }
        } else {
            $dirty = false;
            if ($user['user']['individual_id'] != $existingUser['individual_id']) {
                $existingUser['individual_id'] = $user['user']['individual_id'];
                $dirty = true;
            }
            if ($user['user']['role_id'] != $existingUser['role_id']) {
                $existingUser['role_id'] = $user['user']['role_id'];
                $dirty = true;
            }
            $existingUser;
            if ($dirty) {
                if (!$this->save($existingUser)) {
                    return false;
                }
            }
        }
        return $existingUser;
    }

    private function captureIndividual($user)
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

    private function captureOrganisation($user)
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

    private function captureRole($user)
    {
        $role = $this->Roles->find()->where(['name' => $user['role']['name']])->first();
        if (empty($role)) {
            if (!empty(Configure::read('keycloak.default_role_name'))) {
                $default_role_name = Configure::read('keycloak.default_role_name');
                $role = $this->Roles->find()->where(['name' => $default_role_name])->first();
            }
            if (empty($role)) {
                throw new NotFoundException(__('Invalid role'));
            }
        }
        return $role->id;
    }

    public function getUser(EntityInterface $profile, Session $session)
    {
        $userId = $session->read('Auth.User.id');
        if ($userId) {
            return $this->get($userId);
        }

        $raw_profile_payload = $profile->access_token->getJwt()->getPayload();
        $user = $this->extractKeycloakProfileData($raw_profile_payload);
        if (!$user) {
            throw new \RuntimeException('Unable to save new user');
        }

        return $user;
    }
}
