<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type;
use Cake\ORM\Table;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Cake\Http\Exception\NotFoundException;

use App\Utility\UI\Notification;

Type::map('json', 'Cake\Database\Type\JsonType');

class InboxTable extends AppTable
{

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->addBehavior('Timestamp');
        $this->addBehavior('AuditLog');
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

    public function checkUserBelongsToBroodOwnerOrg($user, $entryData) {
        $this->Broods = \Cake\ORM\TableRegistry::getTableLocator()->get('Broods');
        $this->Individuals = \Cake\ORM\TableRegistry::getTableLocator()->get('Individuals');
        $errors = [];
        $originUrl = trim($entryData['origin'], '/');
        $brood = $this->Broods->find()
            ->where([
                'url IN' => [$originUrl, "{$originUrl}/"]
            ])
            ->first();
        if (empty($brood)) {
            $errors[] = __('Unkown brood `{0}`', $entryData['data']['cerebrateURL']);
        }

        // $found = false;
        // foreach ($user->individual->organisations as $organisations) {
        //     if ($organisations->id == $brood->organisation_id) {
        //         $found = true;
        //     }
        // }
        // if (!$found) {
        //     $errors[] = __('User `{0}` is not part of the brood\'s organisation. Make sure `{0}` is aligned with the organisation owning the brood.', $user->individual->email);
        // }
        return $errors;
    }

    public function createEntry($entryData)
    {
        $savedEntry = $this->save($entryData);
        return $savedEntry;
    }

    public function collectNotifications(\App\Model\Entity\User $user): array
    {
        $allNotifications = [];
        $inboxNotifications = $this->getNotificationsForUser($user);
        foreach ($inboxNotifications as $notification) {
            $title = __('New message');
            $details = $notification->title;
            $router = [
                'controller' => 'inbox',
                'action' => 'process',
                'plugin' => null,
                $notification->id
            ];
            $allNotifications[] = (new Notification($title, $router, [
                'icon' => 'envelope',
                'details' => $details,
                'datetime' => $notification->created,
                'variant' => 'warning',
                '_useModal' => true,
                '_sidebarId' => 'inbox',
            ]))->get();
        }
        return $allNotifications;
    }

    public function getNotificationsForUser(\App\Model\Entity\User $user): array
    {
        $query = $this->find();
        $conditions = [];
        if ($user['role']['perm_admin']) {
            // Admin will not see notifications if it doesn't belong to them. They can see process the message from the inbox
            $conditions['Inbox.user_id IS'] = null;
        } else {
            $conditions['Inbox.user_id'] = $user->id;
        }
        $query->where($conditions);
        $notifications = $query->all()->toArray();
        return $notifications;
    }
}
