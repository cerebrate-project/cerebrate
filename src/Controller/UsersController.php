<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use Cake\Http\Exception\NotFoundException;

class UsersController extends AppController
{
    public $filterFields = [
        'Individuals.email',
        'username',
        'disabled',
        'Individuals.first_name',
        'Individuals.last_name',
        'Individuals.uuid',
        ['name' => 'Organisations.id', 'multiple' => true, 'options' => 'getAllOrganisations', 'select2' => true],
        'Organisations.nationality',
        ['name' => 'Roles.id', 'multiple' => true, 'options' => 'getAllRoles', 'select2' => true],
    ];
    public $quickFilterFields = ['Individuals.uuid', ['username' => true], ['Individuals.first_name' => true], ['Individuals.last_name' => true], 'Individuals.email'];
    public $containFields = ['Individuals', 'Roles', 'UserSettings', 'Organisations', 'OrgGroups'];

    public function index()
    {
        $currentUser = $this->ACL->getUser();
        $conditions = [];
        $validOrgIDsFOrEdition = [];
        if (empty($currentUser['role']['perm_community_admin'])) {
            $conditions['organisation_id IN'] = [$currentUser['organisation_id']];
            if (!empty($currentUser['role']['perm_group_admin'])) {
                $this->loadModel('OrgGroups');
                $validOrgIDsFOrEdition = array_merge($conditions['organisation_id IN'], $this->OrgGroups->getGroupOrgIdsForUser($currentUser));
                $conditions['organisation_id IN'] = $validOrgIDsFOrEdition;
            }
        }
        $keycloakUsersParsed = null;
        if (!empty(Configure::read('keycloak.enabled'))) {
            // $keycloakUsersParsed = $this->Users->getParsedKeycloakUser();
        }
        $additionalContainFields = [];
        if ($this->ParamHandler->isRest()) {
            $additionalContainFields[] = 'MetaFields';
        }
        $containFields = array_merge($this->containFields, $additionalContainFields);
        $this->CRUD->index([
            'contain' => $containFields,
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
            'conditions' => $conditions,
            'afterFind' => function($data) use ($keycloakUsersParsed, $currentUser) {
                $data->_canBeEdited = $this->ACL->canEditUser($currentUser, $data);
                // TODO: We might want to uncomment this at some point Still need to evaluate the impact
                // if (!empty(Configure::read('keycloak.enabled'))) {
                //     $keycloakUser = $keycloakUsersParsed[$data->username];
                //     $data['keycloak_status'] = array_values($this->Users->checkKeycloakStatus([$data->toArray()], [$keycloakUser]))[0];
                // }
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set(
            'validRoles',
            $this->Users->Roles->find('list')->select(['id', 'name'])->order(['name' => 'asc'])->where(['perm_community_admin' => 0, 'perm_org_admin' => 0])->all()->toArray()
        );
        $this->set('validOrgIDsFOrEdition', $validOrgIDsFOrEdition);
    }

    public function filtering()
    {
        $this->CRUD->filtering([
            'afterFind' => function($filtersConfig, $typeMap) {
                $filtersConfig['disabled']['options'] = [
                    '' => __('-- All --'),
                    '0' => __('Enabled'),
                    '1' => __('Disabled'),
                ];
                $filtersConfig['disabled']['multiple'] = false;
                $filtersConfig['disabled']['select2'] = true;
                return [
                    'filtersConfig' => $filtersConfig,
                    'typeMap' => $typeMap,
                ];
            }
        ]);
    }

    public function add()
    {
        $currentUser = $this->ACL->getUser();
        $validRoles = [];
        $individuals_params = [
            'sort' => ['email' => 'asc']
        ];
        $individual_ids = [];
        if (!$currentUser['role']['perm_community_admin']) {
            if ($currentUser['role']['perm_group_admin']) {
                $validRoles = $this->Users->Roles->find('list')->select(['id', 'name'])->order(['name' => 'asc'])->where(['perm_community_admin' => 0, 'perm_group_admin' => 0, 'perm_admin' => 0])->all()->toArray();
                $individual_ids = $this->Users->Individuals->find('aligned', ['organisation_id' => $currentUser['organisation_id']])->all()->extract('id')->toArray();
            } else {
                $validRoles = $this->Users->Roles->find('list')->select(['id', 'name'])->order(['name' => 'asc'])->where(['perm_community_admin' => 0, 'perm_group_admin' => 0, 'perm_org_admin' => 0, 'perm_admin' => 0])->all()->toArray();

            }
            if (empty($individual_ids)) {
                $individual_ids = [-1];
            }
            $individuals_params['conditions'] = ['id IN' => $individual_ids];
        } else {
            $validRoles = $this->Users->Roles->find('list')->order(['name' => 'asc'])->all()->toArray();
        }
        $defaultRole = $this->Users->Roles->find()->select(['id'])->where(['is_default' => true])->first();
        if (empty($defaultRole)) {
            $defaultRole = $this->Users->Roles->find()->select(['id'])->first();
        }
        $defaultRole = $defaultRole->toArray();
        $individuals = $this->Users->Individuals->find('list', $individuals_params)->toArray();
        $this->CRUD->add([
            'beforeMarshal' => function($data) {
                if (empty($data['password'])) {
                    $data['password'] = Security::randomString(20);
                }
                return $data;
            },
            'beforeSave' => function($data) use ($currentUser, $validRoles, $defaultRole, $individual_ids) {
                if (!isset($data['role_id']) && !empty($defaultRole)) {
                    $data['role_id'] = $defaultRole['id'];
                }
                if (!$currentUser['role']['perm_community_admin']) {
                    $validOrgs = $this->Users->getValidOrgsForUser($currentUser);
                    if ($currentUser['role']['perm_group_admin']) {
                        if (!empty($data['organisation_id']) && !in_array($currentUser['organisation_id'], $validOrgs)) {
                            throw new MethodNotAllowedException(__('You do not have permission to assign that organisation.'));
                        }
                    } else {
                        $data['organisation_id'] = $currentUser['organisation_id'];
                    }
                    if (!in_array($data['role_id'], array_keys($validRoles))) {
                        throw new MethodNotAllowedException(__('You do not have permission to assign that role.'));
                    }
                }
                if ((!isset($data['individual_id']) || $data['individual_id'] === 'new') && !empty($data['individual'])) {
                    $existingOrg = $this->Users->Organisations->find('all')->where(['id' => $data['organisation_id']])->select(['uuid'])->first();
                    if (empty($existingOrg)) {
                        throw new MethodNotAllowedException(__('No valid organisation found. Either encode the organisation separately or select a valid one.'));
                    }
                    $data['individual']['alignments'][] = ['type' => 'Member', 'organisation' => ['uuid' => $existingOrg['uuid']]];
                    $data['individual_id'] = $this->Users->Individuals->captureIndividual($data['individual'], true);
                } else if (!$currentUser['role']['perm_community_admin'] && isset($data['individual_id'])) {
                    if (!in_array($data['individual_id'], $individual_ids)) {
                        throw new MethodNotAllowedException(__('The selected individual is not aligned with your organisation. Creating a user for them is not permitted.'));
                    }
                }
                if (empty($data['individual_id'])) {
                    throw new MethodNotAllowedException(__('No valid individual found. Either supply it in the request or set the individual_id to a valid value.'));
                }
                if (Configure::read('keycloak.enabled')) {
                    $existingUserForIndividual = $this->Users->find()->where(['individual_id' => $data['individual_id']])->first();
                    if (!empty($existingUserForIndividual)) {
                        throw new MethodNotAllowedException(__('Invalid individual selected - when KeyCloak is enabled, only one user account may be assigned to an individual.'));
                    }
                }
                return $data;
            },
            'afterSave' => function($data) {
                if (Configure::read('keycloak.enabled')) {
                    $this->Users->enrollUserRouter($data);
                }
                if ($data['individual_id']) {
                    $data['individual'] = $this->Users->Individuals->find('all')->where(['id' => $data['individual_id']])->contain(['Alignments' => 'Organisations'])->first();
                }
                return $data;
            },
            'afterFind' => function ($user, &$params) use ($currentUser) {
                if (!empty($user)) { // We don't have a 404
                    $user = $this->fetchTable('PermissionLimitations')->attachLimitations($user);
                }
                return $user;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        /*
        $alignments = $this->Users->Individuals->Alignments->find('list', [
            //'keyField' => 'id',
            'valueField' => 'organisation_id',
            'groupField' => 'individual_id'
        ])->toArray();
        $alignments = array_map(function($value) { return array_values($value); }, $alignments);
        */
        $org_conditions = [];
        if (empty($currentUser['role']['perm_community_admin'])) {
            $validOrgs = $this->Users->getValidOrgsForUser($currentUser);
            $org_conditions = ['id IN' => $validOrgs];
        }
        $dropdownData = [
            'role' => $validRoles,
            'individual' => $individuals,
            'organisation' => $this->Users->Organisations->find('list', [
                'sort' => ['name' => 'asc'],
                'conditions' => $org_conditions
            ])->toArray()
        ];
        $this->set(compact('dropdownData'));
        $this->set('defaultRole', $defaultRole['id'] ?? null);
        $this->set('metaGroup', $this->isCommunityAdmin ? 'Administration' : 'Cerebrate');
    }

    public function view($id = false)
    {
        $currentUser = $this->ACL->getUser();
        if (empty($id) || (empty($currentUser['role']['perm_org_admin']) && empty($currentUser['role']['perm_community_admin']))) {
            $id = $this->ACL->getUser()['id'];
        }
        $keycloakUsersParsed = null;
        if (!empty(Configure::read('keycloak.enabled'))) {
            try {
                $keycloakUsersParsed = $this->Users->getParsedKeycloakUser();
            } catch (\Exception $e) {
                $keycloakUsersParsed = [];
                $this->Flash->error(__('Issue while connecting to keycloak. {0}', $e->getMessage()));
            }
        }
        $this->CRUD->view($id, [
            'contain' => ['Individuals' => ['Alignments' => 'Organisations'], 'Roles', 'Organisations', 'OrgGroups'],
            'afterFind' => function($data) use ($keycloakUsersParsed, $currentUser) {
                if (
                    empty($currentUser['role']['perm_community_admin']) && 
                    ($currentUser['organisation_id'] != $data['organisation_id']) &&
                    (empty($currentUser['role']['perm_group_admin']) || !$this->ACL->canEditUser($currentUser, $data))
                ) {
                    throw new NotFoundException(__('Invalid User.'));
                }
                $data = $this->fetchTable('PermissionLimitations')->attachLimitations($data);
                if (!empty(Configure::read('keycloak.enabled'))) {
                    $keycloakUser = $keycloakUsersParsed[$data->username] ?? [];
                    $data['keycloak_status'] = array_values($this->Users->checkKeycloakStatus([$data->toArray()], [$keycloakUser]))[0];
                }
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $userToEdit = $this->Users->find()->where(['Users.id' => $id])->contain('Roles')->first();
        $this->set('canEdit', $this->ACL->canEditUser($this->ACL->getUser(), $userToEdit));
        $this->set('keycloakConfig', Configure::read('keycloak', ['enabled' => false]));
    }

    public function edit($id = false)
    {
        $currentUser = $this->ACL->getUser();
        $validRoles = [];
        $validOrgIds = [];
        if (!$currentUser['role']['perm_community_admin']) {
            if ($currentUser['role']['perm_group_admin']) {
                $validRoles = $this->Users->Roles->find('list')->select(['id', 'name'])->order(['name' => 'asc'])->where(['perm_community_admin' => 0, 'perm_group_admin' => 0, 'perm_admin' => 0])->all()->toArray();
                $validOrgIds = $this->Users->Organisations->OrgGroups->getGroupOrgIdsForUser($currentUser);
            } else {
                $validRoles = $this->Users->Roles->find('list')->select(['id', 'name'])->order(['name' => 'asc'])->where(['perm_community_admin' => 0, 'perm_group_admin' => 0, 'perm_org_admin' => 0, 'perm_admin' => 0])->all()->toArray();
            }
        } else {
            $validRoles = $this->Users->Roles->find('list')->order(['name' => 'asc'])->all()->toArray();
        }
        if (empty($id)) {
            $id = $currentUser['id'];
        } else {
            $id = intval($id);
        }

        $params = [
            'removeEmpty' => [
                'password'
            ],
            'fields' => [
                'password', 'confirm_password'
            ],
            'contain' => ['Roles', ],
        ];
        if ($this->request->is(['get'])) {
            $params['fields'] = array_merge($params['fields'], ['role_id', 'disabled']);
            if (!empty($this->ACL->getUser()['role']['perm_community_admin']) || !empty($this->ACL->getUser()['role']['perm_group_admin'])) {
                $params['fields'][] = 'organisation_id';
            }
            if (!$currentUser['role']['perm_community_admin']) {
                $params['afterFind'] = function ($user, &$params) use ($currentUser) {
                    if (!empty($user)) { // We don't have a 404
                        if (!$this->ACL->canEditUser($currentUser, $user)) {
                            throw new MethodNotAllowedException(__('You cannot edit the given user.'));
                        }
                        $user = $this->fetchTable('PermissionLimitations')->attachLimitations($user);
                    }
                    return $user;
                };
            } else {
                $params['afterFind'] = function ($user, &$params) use ($currentUser) {
                    if (!empty($user)) { // We don't have a 404
                        $user = $this->fetchTable('PermissionLimitations')->attachLimitations($user);
                    }
                    return $user;
                };
            }
        }
        if ($this->request->is(['post', 'put']) && !empty($this->ACL->getUser()['role']['perm_community_admin'])) {
            $params['fields'][] = 'role_id';
            $params['fields'][] = 'organisation_id';
            $params['fields'][] = 'disabled';
        } else if (
            $this->request->is(['post', 'put']) && 
            (
                !empty($this->ACL->getUser()['role']['perm_org_admin']) ||
                !empty($this->ACL->getUser()['role']['perm_group_admin'])
            )
        ) {
            if (!empty($this->ACL->getUser()['role']['perm_group_admin'])) {
                $params['fields'][] = 'organisation_id';
            }
            $params['fields'][] = 'role_id';
            $params['fields'][] = 'disabled';
            if (!$currentUser['role']['perm_community_admin']) {
                $params['afterFind'] = function ($data, &$params) use ($currentUser, $validRoles) {
                    if (!in_array($data['role_id'], array_keys($validRoles)) && $this->ACL->getUser()['id'] != $data['id']) {
                        throw new MethodNotAllowedException(__('You cannot edit the given privileged user.'));
                    }
                    if (!$this->ACL->canEditUser($currentUser, $data)) {
                        throw new MethodNotAllowedException(__('You cannot edit the given user.'));
                    }
                    return $data;
                };
                $params['beforeSave'] = function ($data) use ($currentUser, $validRoles, $validOrgIds, $params) {
                    // only run these checks if the user CAN edit them and if the values are actually set in the request
                    if (in_array('role_id', $params['fields']) && isset($data['role_id']) && !in_array($data['role_id'], array_keys($validRoles)) && $this->ACL->getUser()['id'] != $data['id']) {
                        throw new MethodNotAllowedException(__('You cannot assign the chosen role to a user.'));
                    }
                    if (in_array('organisation_id', $params['fields']) && isset($data['organisation_id']) && !in_array($data['organisation_id'], $validOrgIds)) {
                        throw new MethodNotAllowedException(__('You cannot assign the chosen organisation to a user.'));
                    }
                    return $data;
                };
            }
        }
        $this->CRUD->edit($id, $params);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $org_conditions = [];
        if (empty($currentUser['role']['perm_community_admin'])) {
            $org_conditions = ['id' => $currentUser['organisation_id']];
            if (!empty($currentUser['role']['perm_group_admin']) && !empty($validOrgIds)) {
                $org_conditions = ['id IN' => $validOrgIds];
            }
        }
        if ($this->ACL->getUser()['id'] == $id) {
            $validRoles[$this->ACL->getUser()['role']['id']] = $this->ACL->getUser()['role']['name']; // include the current role of the user
        }
        $dropdownData = [
            'role' => $validRoles,
            'organisation' => $this->Users->Organisations->find('list', [
                'sort' => ['name' => 'asc'],
                'conditions' => $org_conditions
            ])->toArray()
        ];
        $this->set(compact('dropdownData'));
        $userToEdit = $this->Users->find()->where(['Users.id' => $id])->contain('Roles')->first();
        $this->set('canEdit', $this->ACL->canEditUser($this->ACL->getUser(), $userToEdit));
        $this->render('add');
    }

    public function toggle($id, $fieldName = 'disabled')
    {
        $params = [
            'contain' => 'Roles'
        ];
        $currentUser = $this->ACL->getUser();
        if (!$currentUser['role']['perm_community_admin']) {
            $params['afterFind'] = function ($user, &$params) use ($currentUser) {
                if (!$this->ACL->canEditUser($currentUser, $user)) {
                    throw new MethodNotAllowedException(__('You cannot edit the given user.'));
                }
                return $user;
            };
        }
        $this->CRUD->toggle($id, $fieldName, $params);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function delete($id)
    {
        $currentUser = $this->ACL->getUser();
        $validRoles = [];
        if (!$currentUser['role']['perm_community_admin']) {
            $validRoles = $this->Users->Roles->find('list')->order(['name' => 'asc'])->all()->toArray();
        }
        $params = [
            'beforeSave' => function($data) use ($currentUser, $validRoles) {
                if (empty(Configure::read('user.allow-user-deletion'))) {
                    throw new MethodNotAllowedException(__('User deletion is disabled on this instance.'));
                }
                if (!$this->ACL->canEditUser($currentUser, $data)) {
                    throw new MethodNotAllowedException(__('You cannot edit the given user.'));
                }
                if (!$currentUser['role']['perm_community_admin']) {
                    if ($data['organisation_id'] !== $currentUser['organisation_id']) {
                        throw new MethodNotAllowedException(__('You do not have permission to delete the given user.'));
                    }
                    if (!in_array($data['role_id'], array_keys($validRoles))) {
                        throw new MethodNotAllowedException(__('You do not have permission to delete the given user.'));
                    }
                }
                if (Configure::read('keycloak.enabled')) {
                    if (!$this->Users->deleteUser($data)) {
                        throw new MethodNotAllowedException(__('Could not delete the user from KeyCloak. Please try again later, or consider disabling the user instead.'));
                    }
                }
                return $data;
            }
        ];
        $this->CRUD->delete($id, $params);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', $this->isCommunityAdmin ? 'Administration' : 'Cerebrate');
    }

    public function massEdit()
    {
        $currentUser = $this->ACL->getUser();
        $validRoles = [];
        $validOrgIds = [];
        if (!$currentUser['role']['perm_community_admin']) {
            if ($currentUser['role']['perm_group_admin']) {
                $validRoles = $this->Users->Roles->find('list')->select(['id', 'name'])->order(['name' => 'asc'])->where(['perm_community_admin' => 0, 'perm_group_admin' => 0, 'perm_admin' => 0])->all()->toArray();
                $validOrgIds = $this->Users->Organisations->OrgGroups->getGroupOrgIdsForUser($currentUser);
            } else {
                $validRoles = $this->Users->Roles->find('list')->select(['id', 'name'])->order(['name' => 'asc'])->where(['perm_community_admin' => 0, 'perm_group_admin' => 0, 'perm_org_admin' => 0, 'perm_admin' => 0])->all()->toArray();
            }
        } else {
            $validRoles = $this->Users->Roles->find('list')->order(['name' => 'asc'])->all()->toArray();
            $validOrgIds = $this->Users->Organisations->find()->all()->extract('id')->toList();
        }

        $org_conditions = [];
        if (empty($currentUser['role']['perm_community_admin'])) {
            $org_conditions = ['id' => $currentUser['organisation_id']];
            if (!empty($currentUser['role']['perm_group_admin']) && !empty($validOrgIds)) {
                $org_conditions = ['id IN' => $validOrgIds];
            }
        }
        $organisations = $this->Users->Organisations->find('list', [
            'sort' => ['name' => 'asc'],
            'conditions' => $org_conditions
        ])->toArray();

        $validFields = ['role_id', 'disabled'];
        if (!empty($currentUser['role']['perm_community_admin']) || !empty($currentUser['role']['perm_group_admin'])) {
            $validFields[] = 'organisation_id';
        }

        $metaFieldsEnabled = $currentUser['role']['perm_meta_field_editor'] && $this->CRUD->metaFieldsSupported();
        if ($metaFieldsEnabled) {
            $MetaTemplates = TableRegistry::getTableLocator()->get('MetaTemplates');
            $metaTemplates = $this->CRUD->getMetaTemplates();
        }

        if ($this->request->is('post') || $this->request->is('put')) {
            $ids = $this->CRUD->getIdsOrFail();
            $editSuccesses = 0;
            $editResults = [];
            $editErrors = [];
            $input = $this->request->getData();
            $inputWithChanges = $this->extractChangedFields($input, $validFields, true);
            if (!empty($inputWithChanges)) {
                foreach ($ids as $id) {
                    $user = $this->Users->get($id, [
                        'contain' => ['MetaFields']
                    ]);
                    $user = $this->CRUD->attachMetaTemplatesIfNeeded($user, $metaTemplates->toArray());

                    if (!$currentUser['role']['perm_community_admin']) {
                        if (!$this->ACL->canEditUser($currentUser, $user)) {
                            throw new MethodNotAllowedException(__('You cannot edit the given user.'));
                        }
                        if (!empty($inputWithChanges['role_id']) && !in_array($user['role_id'], array_keys($validRoles)) && $currentUser['id'] != $user['id']) {
                            throw new MethodNotAllowedException(__('You cannot edit the given privileged user.'));
                        }
                    }

                    // only run these checks if the user CAN edit them and if the values are actually set in the request
                    if (isset($inputWithChanges['role_id']) && !in_array($inputWithChanges['role_id'], array_keys($validRoles)) && $currentUser['id'] != $inputWithChanges['id']) {
                        throw new MethodNotAllowedException(__('You cannot assign the chosen role to a user.'));
                    }
                    if (in_array('organisation_id', $validFields) && isset($inputWithChanges['organisation_id']) && !in_array($inputWithChanges['organisation_id'], $validOrgIds)) {
                        throw new MethodNotAllowedException(__('You cannot assign the chosen organisation to a user.'));
                    }

                    // Adapt input to update values that were changed.
                    $patchEntityParams = [
                        'fields' => $validFields,
                    ];

                    if (!empty($inputWithChanges['MetaTemplates'])) {
                        // Deleting everything and re-created
                        $cleanupMetaFields = [];
                        foreach ($inputWithChanges['MetaTemplates'] as $template_id => $template) {
                            foreach ($template['meta_template_fields'] as $meta_template_field_id => $meta_template_field) {
                                $field = $metaTemplates->toArray()[$template_id]['meta_template_fields'][$meta_template_field_id]->field;
                                $cleanupMetaFields[] = [
                                    'scope' => $this->Users->getBehavior('MetaFields')->getScope(),
                                    'field' => $field,
                                    'meta_template_field_id' => $meta_template_field_id,
                                    'meta_template_id' => $template_id,
                                ];
                            }
                        }
                        $massagedData = $this->CRUD->massageMetaFields($user, $inputWithChanges, $metaTemplates);
                        if (!empty($cleanupMetaFields)) {
                            foreach ($cleanupMetaFields as $cleanupMetaField) {
                                $this->Users->MetaFields->deleteAll([
                                    'AND' => [
                                        'scope' => $cleanupMetaField['scope'],
                                        'field' => $cleanupMetaField['field'],
                                        'meta_template_field_id' => $cleanupMetaField['meta_template_field_id'],
                                        'meta_template_id' => $cleanupMetaField['meta_template_id'],
                                        'parent_id' => $id
                                    ]
                                ]);
                            }
                        }
                        unset($input['MetaTemplates']); // Avoid MetaTemplates to be overriden when patching entity
                    }
                    $data = $massagedData['entity'];
                    $metaFieldsToDelete = $massagedData['metafields_to_delete'];
                    if (isset($input['meta_fields'])) {
                        unset($input['meta_fields']);
                    }

                    $data = $this->Users->patchEntity($data, $inputWithChanges, $patchEntityParams);
                    $savedData = $this->Users->save($data);
                    if ($savedData !== false) {
                        if (!empty($metaFieldsToDelete)) {
                            foreach ($metaFieldsToDelete as $k => $v) {
                                if ($v === null) {
                                    unset($metaFieldsToDelete[$k]);
                                }
                            }
                            if (!empty($metaFieldsToDelete)) {
                                $this->Users->MetaFields->unlink($savedData, $metaFieldsToDelete);
                            }
                        }
                        $editResults[] = $savedData;
                        $editSuccesses++;
                    } else {
                        $validationErrors = $data->getErrors();
                        $validationMessage = $this->CRUD->prepareValidationError($data);
                        $editErrors[] = $validationMessage;
                    }

                }
            }
            if (empty($inputWithChanges)) {
                $success = true;
                $message = __('No changes applied.');
            } else {
                $success = $editSuccesses == count($ids);
                $message = __(
                    '{0} {1} have been modified.',
                    $editSuccesses == count($ids) ? __('All') : sprintf('%s / %s', $editSuccesses, count($ids)),
                    Inflector::pluralize($this->Users->getAlias())
                );
                if (!$success) {
                    $message .= __(' Errors: {0}', implode(', ', $editErrors));
                }
            }
            $this->CRUD->setResponseForController('massEdit', $success, $message, $editResults, $editResults, $editErrors);
            $responsePayload = $this->CRUD->getResponsePayload();
            if (!empty($responsePayload)) {
                return $responsePayload;
            }
        }
        $validRoles += ['unchanged' => __('-- Unchanged --')];
        $organisations += ['unchanged' => __('-- Unchanged --')];
        $dropdownData = [
            'role' => $validRoles,
            'organisation' => $organisations
        ];
        $this->set(compact('dropdownData'));
        $this->set('defaultRole', 'unchanged');
        $this->set('defaultOrg', 'unchanged');
        $this->set('defaultDisabledState', 'unchanged');
        $this->set('validFields', $validFields);

        $data = $this->Users->newEmptyEntity();
        $metaFieldsEnabled = $currentUser['role']['perm_meta_field_editor'] && $this->CRUD->metaFieldsSupported();
        if ($metaFieldsEnabled) {
            $metaTemplates = $MetaTemplates->transformTemplatesInputsForUnchangedSupport($metaTemplates->toList());
            $data = $this->CRUD->attachMetaTemplates($data, $metaTemplates);
        }
        $this->set('entity', $data);

        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function login()
    {
        $blocked = false;
        if ($this->request->is('post')) {
            $BruteforceTable = TableRegistry::getTableLocator()->get('Bruteforces');
            $input = $this->request->getData();
            $blocked = $BruteforceTable->isBlocklisted($_SERVER['REMOTE_ADDR'], $input['username']);
            if ($blocked) {
                $this->Authentication->logout();
                $this->Flash->error(__('Too many attempts, brute force protection triggered. Wait 5 minutes before trying again.'));
                $this->redirect(['controller' => 'users', 'action' => 'login']);
            }
        }
        if (!$blocked) {
            $result = $this->Authentication->getResult();
            // If the user is logged in send them away.
            $logModel = $this->Users->auditLogs();
            if ($result->isValid()) {
                $user = $logModel->userInfo();
                $logModel->insert([
                    'request_action' => 'login',
                    'model' => 'Users',
                    'model_id' => $user['id'],
                    'model_title' => $user['name'],
                    'changed' => []
                ]);
                $target = $this->Authentication->getLoginRedirect() ?? '/instance/home';
                return $this->redirect($target);
            }
            if ($this->request->is('post') && !$result->isValid()) {
                $BruteforceTable->insert($_SERVER['REMOTE_ADDR'], $input['username']);
                $logModel->insert([
                    'request_action' => 'login_fail',
                    'model' => 'Users',
                    'model_id' => 0,
                    'model_title' => 'unknown_user',
                    'changed' => []
                ]);
                $this->Flash->error(__('Invalid username or password'));
            }
        }
        $this->viewBuilder()->setLayout('login');
    }

    public function logout()
    {
        $result = $this->Authentication->getResult();
        if ($result->isValid()) {
            $logModel = $this->Users->auditLogs();
            $user = $logModel->userInfo();
            $logModel->insert([
                'request_action' => 'logout',
                'model' => 'Users',
                'model_id' => $user['id'],
                'model_title' => $user['name'],
                'changed' => []
            ]);
            $this->Authentication->logout();
            $this->Flash->success(__('Goodbye.'));
            if (Configure::read('keycloak.enabled')) {
                $this->redirect($this->Users->keyCloaklogout());
            }
            $this->request->getSession()->destroy();
            return $this->redirect(\Cake\Routing\Router::url('/users/login'));
        }
    }

    public function settings($user_id=false)
    {
        $editingAnotherUser = false;
        $currentUser = $this->ACL->getUser();
        if ((empty($currentUser['role']['perm_community_admin']) && empty($currentUser['role']['perm_group_admin'])) || empty($user_id) || $user_id == $currentUser->id) {
            $user = $currentUser;
        } else {
            $user = $this->Users->get($user_id, [
                'contain' => ['Roles', 'Individuals' => 'Organisations', 'Organisations', 'UserSettings']
            ]);
            $editingAnotherUser = true;
            if (!empty($currentUser['role']['perm_group_admin']) && !$this->ACL->canEditUser($currentUser, $user)) {
                $user = $currentUser;
                $editingAnotherUser = false;
            }
        }
        $this->set('editingAnotherUser', $editingAnotherUser);
        $this->set('user', $user);
        $all = $this->Users->UserSettings->getSettingsFromProviderForUser($user->id, true);
        $this->set('settingsProvider', $all['settingsProvider']);
        $this->set('settings', $all['settings']);
        $this->set('settingsFlattened', $all['settingsFlattened']);
        $this->set('notices', $all['notices']);
    }

    public function register()
    {
        if (empty(Configure::read('security.registration.self-registration'))) {
            throw new UnauthorizedException(__('User self-registration is not open.'));
        }
        if (!Configure::check('security.registration.floodProtection') || Configure::read('security.registration.floodProtection')) {
            $this->FloodProtection->check('register');
        }
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $this->InboxProcessors = TableRegistry::getTableLocator()->get('InboxProcessors');
            $processor = $this->InboxProcessors->getProcessor('User', 'Registration');
            $data = [
                'origin' => $this->request->clientIp(),
                'comment' => '-no comment-',
                'data' => [
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'password' => $data['password'],
                    'org_name' => $data['org_name'],
                    'org_uuid' => $data['org_uuid'],
                ],
            ];
            $processorResult = $processor->create($data);
            if (!empty(Configure::read('security.registration.floodProtection'))) {
                $this->FloodProtection->set('register');
            }
            return $processor->genHTTPReply($this, $processorResult, ['controller' => 'Inbox', 'action' => 'index']);
        }
        $this->viewBuilder()->setLayout('login');
    }

    public function getLimitationForOrganisation($org_id) {
        $currentUser = $this->ACL->getUser();
        if (!$currentUser['role']['perm_community_admin']) {
            $validOrgs = $this->Users->getValidOrgsForUser($currentUser);
            if ($currentUser['role']['perm_group_admin']) {
                if (!in_array($org_id, $validOrgs)) {
                    throw new MethodNotAllowedException(__('You do not have permission to assign that organisation.'));
                }
            }
        }
        $fakeUser = $this->Users->newEmptyEntity();
        $fakeUser->organisation_id = $org_id; // set fakeUser's to the selected org-id
        $metaTemplates = $this->CRUD->getMetaTemplates();
        $fakeUser = $this->CRUD->attachMetaTemplatesIfNeeded($fakeUser, $metaTemplates->toArray());
        $fakeUser = $this->fetchTable('PermissionLimitations')->attachLimitations($fakeUser);
        return $this->RestResponse->viewData($fakeUser, 'json');
    }

    public function extractChangedFields(array $input, array $validFields=[], $checkForMetatemplate=false): array
    {
        $inputWithChanges = [];
        foreach ($validFields as $field) {
            if ($input[$field] != 'unchanged') {
                $inputWithChanges[$field] = $input[$field];
            }
        }

        if ($checkForMetatemplate && !empty($input['MetaTemplates'])) {
            foreach ($input['MetaTemplates'] as $template_id => $meta_template_fields) {
                foreach ($meta_template_fields['meta_template_fields'] as $field_id => $meta_field) {
                    if ($meta_field['metaFields']['new'][0] != 'unchanged') {
                        $inputWithChanges['MetaTemplates'][$template_id]['meta_template_fields'][$field_id] = $meta_field;
                    }
                }
            }
        }

        return $inputWithChanges;
    }
}
