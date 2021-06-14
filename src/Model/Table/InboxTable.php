<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type;
use Cake\ORM\Table;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Cake\Http\Exception\NotFoundException;

Type::map('json', 'Cake\Database\Type\JsonType');

class InboxTable extends AppTable
{

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new'
                ]
            ]
        ]);

        $this->belongsTo('Users');
        $this->setDisplayField('title');
    }

    protected function _initializeSchema(TableSchemaInterface $schema): TableSchemaInterface
    {
        $schema->setColumnType('data', 'json');

        return $schema;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('scope')
            ->notEmptyString('action')
            ->notEmptyString('title')
            ->notEmptyString('origin')
            ->datetime('created')

            ->requirePresence([
                'scope' => ['message' => __('The field `scope` is required')],
                'action' => ['message' => __('The field `action` is required')],
                'title' => ['message' => __('The field `title` is required')],
                'origin' => ['message' => __('The field `origin` is required')],
            ], 'create');
        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('user_id', 'Users'), [
            'message' => 'The provided `user_id` does not exist'
        ]);

        return $rules;
    }

    public function sendRequest($brood, $urlPath, $methodPost = true, $data = []): boolean
    {
        $http = new Client();
        $config = [
            'headers' => [
                'AUTHORIZATION' => $brood->authkey,
                'Accept' => 'application/json'
            ],
            'type' => 'json'
        ];
        $url = $brood->url . $urlPath;
        if ($methodPost) {
            $response = $http->post($url, json_encode(data), $config);
        } else {
            $response = $http->get($brood->url, json_encode(data), $config);
        }
        if ($response->isOk()) {
            return $response;
        } else {
            throw new NotFoundException(__('Could not post to the requested resource.'));
        }
    }

    public function checkUserBelongsToBroodOwnerOrg($user, $entryData) {
        $this->Broods = \Cake\ORM\TableRegistry::getTableLocator()->get('Broods');
        $this->Individuals = \Cake\ORM\TableRegistry::getTableLocator()->get('Individuals');
        $errors = [];
        $brood = $this->Broods->find()
            ->where(['url' => $entryData['origin']])
            ->first();
        if (empty($brood)) {
            $errors[] = __('Unkown brood `{0}`', $entryData['data']['cerebrateURL']);
        }
        
        $found = false;
        foreach ($user->individual->organisations as $organisations) {
            if ($organisations->id == $brood->organisation_id) {
                $found = true;
            }
        }
        if (!$found) {
            $errors[] = __('User is not part of the brood organisation');
        }
        return $errors;
    }
}
