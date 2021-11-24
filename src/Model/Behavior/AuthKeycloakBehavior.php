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
use \Cake\Http\Session;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Client\FormData;

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
        ]);
        $token = $this->getAdminAccessToken();
        $keyCloakUser = [
            'firstName' => $individual['first_name'],
            'lastName' => $individual['last_name'],
            'username' => $data['username'],
            'email' =>  $individual['email'],
            'attributes' => [
                'role_name' => empty($role['name']) ? Configure::read('keycloak.default_role_name') : $role['name'],
                'org_uuid' => $orgs['uuid']
            ]
        ];
        $keycloakConfig = Configure::read('keycloak');
        $http = new Client();
        $url = sprintf(
            '%s/admin/realms/%s/users',
            $keycloakConfig['provider']['baseUrl'],
            $keycloakConfig['provider']['realm']
        );
        $response = $http->post(
            $url,
            json_encode($keyCloakUser),
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]
        );
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
}
