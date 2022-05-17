<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Utility\Text;
use Cake\Utility\Security;
use Cake\Utility\Hash;
use \Cake\Http\Session;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Client\FormData;
use Cake\Http\Exception\NotFoundException;

class AuthKeycloakBehavior extends Behavior
{

    public function getUser(EntityInterface $profile, Session $session)
    {
        $userId = $session->read('Auth.User.id');
        if ($userId) {
            return $this->_table->get($userId);
        }

        $raw_profile_payload = $profile->access_token->getJwt()->getPayload();
        $user = $this->extractProfileData($raw_profile_payload);
        if (!$user) {
            throw new \RuntimeException('Unable to save new user');
        }

        return $user;
    }

    private function extractProfileData($profile_payload)
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
        $user['user']['individual_id'] = $this->_table->captureIndividual($user);
        $user['user']['role_id'] = $this->_table->captureRole($user);
        $existingUser = $this->_table->find()->where(['username' => $user['user']['username']])->first();
        if (empty($existingUser)) {
            $user['user']['password'] = Security::randomString(16);
            $existingUser = $this->_table->newEntity($user['user']);
            if (!$this->_table->save($existingUser)) {
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
                if (!$this->_table->save($existingUser)) {
                    return false;
                }
            }
        }
        return $existingUser;
    }

    /*
     * Run a rest query against keycloak
     * Auto sets the headers and uses a sprintf string to build the URL, injecting the baseurl + realm into the $pathString
     */
    private function restApiRequest(string $pathString, array $payload, string $postRequestType = 'post'): Object
    {
        $token = $this->getAdminAccessToken();
        $keycloakConfig = Configure::read('keycloak');
        $http = new Client();
        $url = sprintf(
            $pathString,
            $keycloakConfig['provider']['baseUrl'],
            $keycloakConfig['provider']['realm']
        );
        return $http->$postRequestType(
            $url,
            json_encode($payload),
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]
        );
    }

    public function enrollUser($data): bool
    {
        $individual = $this->_table->Individuals->find()->where(
            ['id' => $data['individual_id']]
        )->first();
        $roleConditions = [
            'id' => $data['role_id']
        ];
        if (!empty(Configure::read('keycloak.user_management.actions'))) {
            $roleConditions['name'] = Configure::read('keycloak.default_role_name');
        }
        $role = $this->_table->Roles->find()->where($roleConditions)->first();
        $org = $this->_table->Organisations->find()->where([
            ['id' => $data['organisation_id']]
        ])->first();
        $keyCloakUser = [
            'firstName' => $individual['first_name'],
            'lastName' => $individual['last_name'],
            'username' => $data['username'],
            'email' =>  $individual['email'],
            'enabled' => true,
            'attributes' => [
                'role_name' => empty($role['name']) ? Configure::read('keycloak.default_role_name') : $role['name'],
                'org_uuid' => $org['uuid']
            ]
        ];
        $path = '%s/admin/realms/%s/users';
        $response = $this->restApiRequest($path, $keyCloakUser, 'post');
        $logChange = [
            'username' => $data['username'],
            'individual_id' => $data['individual_id'],
            'role_id' => $data['role_id']
        ];
        if (!$response->isOk()) {
            $logChange['error_code'] = $response->getStatusCode();
            $logChange['error_body'] = $response->getStringBody();
            $this->_table->auditLogs()->insert([
                'request_action' => 'enrollUser',
                'model' => 'User',
                'model_id' => 0,
                'model_title' => __('Failed Keycloak enrollment for user {0}', $data['username']),
                'changed' => $logChange
            ]);
        } else {
            $this->_table->auditLogs()->insert([
                'request_action' => 'enrollUser',
                'model' => 'User',
                'model_id' => 0,
                'model_title' => __('Successful Keycloak enrollment for user {0}', $data['username']),
                'changed' => $logChange
            ]);
        }
        return true;
    }

    private function getAdminAccessToken()
    {
        $keycloakConfig = Configure::read('keycloak');
        $http = new Client();
        $tokenUrl = sprintf(
            '%s/realms/%s/protocol/openid-connect/token',
            $keycloakConfig['provider']['baseUrl'],
            $keycloakConfig['provider']['realm']
        );
        $response = $http->post(
            $tokenUrl,
            sprintf(
                'grant_type=client_credentials&client_id=%s&client_secret=%s',
                urlencode(Configure::read('keycloak.provider.applicationId')),
                urlencode(Configure::read('keycloak.provider.applicationSecret'))
            ),
            [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ]
            ]
        );
        $parsedResponse = json_decode($response->getStringBody(), true);
        return $parsedResponse['access_token'];
    }

    private function getClientId(): string
    {
        $response = $this->restApiRequest('%s/admin/realms/%s/clients?clientId=' . Configure::read('keycloak.provider.applicationId'), [], 'get');
        $clientId = json_decode($response->getStringBody(), true);
        if (!empty($clientId[0]['id'])) {
            return $clientId[0]['id'];
        } else {
            throw new NotFoundException(__('Keycloak client ID not found or service account doesn\'t have the "view-clients" privilege.'));
        }
    }

    public function syncWithKeycloak(): array
    {
        $results = [];
        $data['Roles'] = $this->_table->Roles->find()->disableHydration()->toArray();
        $data['Organisations'] = $this->_table->Organisations->find()->disableHydration()->toArray();
        $data['Users'] = $this->_table->find()->contain(['Individuals', 'Organisations', 'Roles'])->select(
            [
                'id',
                'uuid',
                'username',
                'disabled',
                'Individuals.email',
                'Individuals.first_name',
                'Individuals.last_name',
                'Individuals.uuid',
                'Roles.name',
                'Roles.uuid',
                'Organisations.name',
                'Organisations.uuid'
            ]
        )->disableHydration()->toArray();
        $clientId = $this->getClientId();
        $modified = 0;
        $modified += $this->syncRoles(Hash::extract($data['Roles'], '{n}.name'), $clientId, 'Role');
        $modified += $this->syncRoles(Hash::extract($data['Organisations'], '{n}.name'), $clientId, 'Organisation');
        $modified += $this->syncUsers($data['Users'], $clientId);
        return $results;
    }

    private function syncRoles(array $roles, string $clientId, string $scope = 'Role'): int
    {
        $keycloakRoles = $this->getAllRoles($clientId);
        $keycloakRolesParsed = Hash::extract($keycloakRoles, '{n}.name');
        $rolesToAdd = [];
        $scopeString = $scope . ':';
        $modified = 0;
        foreach ($roles as $role) {
            if (!in_array($scopeString . $role, $keycloakRolesParsed)) {
                $roleToPush = [
                    'name' => $scopeString . $role,
                    'clientRole' => true
                ];
                $url = '%s/admin/realms/%s/clients/' . $clientId . '/roles';
                $this->restApiRequest($url, $roleToPush, 'post');
                $modified += 1;
            }
            $keycloakRolesParsed = array_diff($keycloakRolesParsed, [$scopeString . $role]);
        }
        foreach ($keycloakRolesParsed as $roleToRemove) {
            if (substr($roleToRemove, 0, strlen($scopeString)) === $scopeString) {
                $url = '%s/admin/realms/%s/clients/' . $clientId . '/roles/' . $roleToRemove;
                $this->restApiRequest($url, [], 'delete');
                $modified += 1;
            }
        }
        return $modified;
    }

    private function getAllRoles(string $clientId): array
    {
        $response = $this->restApiRequest('%s/admin/realms/%s/clients/' . $clientId . '/roles', [], 'get');
        return json_decode($response->getStringBody(), true);
    }

    private function syncUsers(array $users, $clientId, $roles = null): bool
    {
        if ($roles === null) {
            $roles = $this->getAllRoles($clientId);
        }
        $rolesParsed = [];
        foreach ($roles as $role) {
            $rolesParsed[$role['name']] = $role['id'];
        }
        $response = $this->restApiRequest('%s/admin/realms/%s/users', [], 'get');
        $keycloakUsers = json_decode($response->getStringBody(), true);
        $keycloakUsersParsed = [];
        foreach ($keycloakUsers as $u) {
            $response = $this->restApiRequest('%s/admin/realms/%s/users/' . $u['id'] . '/role-mappings/clients/' . $clientId, [], 'get');
            $roleMappings = json_decode($response->getStringBody(), true);
            $keycloakUsersParsed[$u['username']] = [
                'id' => $u['id'],
                'username' => $u['username'],
                'enabled' => $u['enabled'],
                'firstName' => $u['firstName'],
                'lastName' => $u['lastName'],
                'email' => $u['email'],
                'roles' => $roleMappings
            ];
        }
        foreach ($users as &$user) {
            if (empty($keycloakUsersParsed[$user['username']])) {
                $this->createUser($user, $clientId, $rolesParsed);
            } else {
                $this->checkAndUpdateUser($keycloakUsersParsed[$user['username']], $user);
                $this->checkAndUpdateUserRoles($keycloakUsersParsed[$user['username']], $user, $clientId, $rolesParsed);
            }
        }
        return true;
    }

    private function checkAndUpdateUser(array $keycloakUser, array $user): bool
    {
        if (
            $keycloakUser['enabled'] == $user['disabled'] ||
            $keycloakUser['firstName'] !== $user['individual']['first_name'] ||
            $keycloakUser['lastName'] !== $user['individual']['last_name'] ||
            $keycloakUser['email'] !== $user['individual']['email']
        ) {
            $change = [
                'enabled' => !$user['disabled'],
                'firstName' => !$user['individual']['first_name'],
                'lastName' => !$user['individual']['last_name'],
                'email' => !$user['individual']['email'],
            ];
            $response = $this->restApiRequest('%s/admin/realms/%s/users/' . $keycloakUser['id'], $change, 'put');
        }
        return true;
    }

    private function createUser(array $user, string $clientId, array $rolesParsed): bool
    {
        $newUser = [
            'username' => $user['username'],
            'enabled' => !$user['disabled'],
            'firstName' => $user['individual']['first_name'],
            'lastName' => $user['individual']['last_name'],
            'email' => $user['individual']['email']
        ];
        $response = $this->restApiRequest('%s/admin/realms/%s/users', $newUser, 'post');
        $newUser = $this->restApiRequest('%s/admin/realms/%s/users?username=' . urlencode($user['username']), [], 'get');
        $user['id'] = json_decode($newUser->getStringBody(), true);
        $this->assignRolesToUser($user, $rolesParsed, $clientId);
        return true;
    }

    private function assignRolesToUser(array $user, array $rolesParsed, string $clientId): bool
    {
        $roles = [
            [
                'id' => $rolesParsed['Role:' . $user['role']['name']],
                'name' => 'Role:' . $user['role']['name'],
                'clientRole' => true,
                'containerId' => $clientId
            ],
            [
                'id' => $rolesParsed['Organisation:' . $user['organisation']['name']],
                'name' => 'Organisation:' . $user['organisation']['name'],
                'clientRole' => true,
                'containerId' => $clientId
            ]
        ];
        $this->restApiRequest('%s/admin/realms/%s/users/' . $user['id'] . '/role-mappings/clients/' . $clientId, $roles, 'post');
        return true;
    }

    private function checkAndUpdateUserRoles(array $keycloakUser, array $user, string $clientId, array $rolesParsed): bool
    {
        $assignedRoles = $this->restApiRequest('%s/admin/realms/%s/users/' . $keycloakUser['id'] . '/role-mappings/clients/' . $clientId, [], 'get');
        $assignedRoles = json_decode($assignedRoles->getStringBody(), true);
        $keycloakUserRoles = Hash::extract($assignedRoles, '{n}.name');
        $assignedRolesParsed = [];
        foreach ($assignedRoles as $k => $v) {
            $assignedRolesParsed[$v['name']] = $v;
        }
        $userRoles = [
            'Organisation:' . $user['organisation']['name'] => [
                'id' => $rolesParsed['Organisation:' . $user['organisation']['name']],
                'name' => 'Organisation:' . $user['organisation']['name'],
                'clientRole' => true,
                'containerId' => $clientId
            ],
            'Role:' . $user['role']['name'] => [
                'id' => $rolesParsed['Role:' . $user['role']['name']],
                'name' => 'Role:' . $user['role']['name'],
                'clientRole' => true,
                'containerId' => $clientId
            ]
        ];
        $toAdd = array_diff(array_keys($userRoles), $keycloakUserRoles);
        $toRemove = array_diff($keycloakUserRoles, array_keys($userRoles));
        foreach ($toRemove as $k => $role) {
            if (substr($role, 0, strlen('Organisation:')) !== 'Organisation:' && substr($role, 0, strlen('Role:') !== 'Role:')) {
                unset($toRemove[$k]);
            } else {
                $toRemove[$k] = $assignedRolesParsed[$role];
            }
        }
        if (!empty($toRemove)) {
            $toRemove = array_values($toRemove);
            $this->restApiRequest('%s/admin/realms/%s/users/' . $keycloakUser['id'] . '/role-mappings/clients/' . $clientId, $toRemove, 'delete');
        }
        foreach ($toAdd as $k => $name) {
            $toAdd[$k] = $userRoles[$name];
        }
        if (!empty($toAdd)) {
            $response = $this->restApiRequest('%s/admin/realms/%s/users/' . $keycloakUser['id'] . '/role-mappings/clients/' . $clientId, $toAdd, 'post');
        }
        return true;
    }
}
