<?php
namespace App\Model\Behavior;

use ArrayObject;
use App\Model\Entity\AppModel;
use App\Model\Entity\MetaField;
use Cake\Core\Configure;
use App\Model\Table\UsersTable;
use Cake\ORM\Behavior;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Utility\Inflector;
use Cake\Utility\Hash;
use Cake\Routing\Router;

class NotifyAdminsBehavior extends Behavior
{
    /** @var array */
    protected $_defaultConfig = [
        'implementedEvents' => [
            'Model.afterSave' => 'afterSave',
            'Model.afterDelete' => 'afterDelete',
            'Model.beforeDelete' => 'beforeDelete',
        ],
        'implementedMethods' => [
            'notifySiteAdmins' => 'notifySiteAdmins',
            'notifySiteAdminsForEntity' => 'notifySiteAdminsForEntity',
        ],
    ];

    /** @var AuditLog|null */
    private $Inbox;
    /** @var User|null */
    private $Users;
    /** @var InboxProcessors|null */
    private $InboxProcessors;

    public function initialize(array $config): void
    {
        $this->Inbox = TableRegistry::getTableLocator()->get('Inbox');
        if($this->table() instanceof UsersTable) {
            $this->Users = $this->table();
        } else {
            $this->Users = TableRegistry::getTableLocator()->get('Users');
        }
    }

    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (!$this->isNotificationAllowed($event, $entity, $options)) {
            return;
        }
        $this->notifySiteAdminsForEntityChange($entity);
    }

    public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if ($entity->table()->hasBehavior('MetaFields') && !isset($entity->meta_fields)) {
            $entity = $entity->table()->loadInto($entity, ['MetaFields']);
        }
    }

    public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (!$this->isNotificationAllowed($event, $entity, $options)) {
            return;
        }
        $this->notifySiteAdminsForEntityDeletion($entity);
    }

    public function isNotificationAllowed(EventInterface $event, EntityInterface $entity, ArrayObject $options): bool
    {
        $loggedUser = Configure::read('loggedUser');
        if (empty($loggedUser) || !empty($loggedUser['role']['perm_admin']) || !empty($loggedUser['role']['perm_sync'])) {
            return false;
        }
        return true;
    }

    public function notifySiteAdminsForEntityChange(EntityInterface $entity): void
    {
        $watchedFields = !empty($this->getConfig()['fields']) ? $this->getConfig()['fields'] : $entity->getVisible();
        $originalFields = [];
        $changedFields = $entity->extract($watchedFields);

        $titleTemplate = 'New {0} `{1}` created';
        $title = __(
            $titleTemplate,
            Inflector::singularize($entity->getSource()),
            $entity->get($this->table()->getDisplayField())
        );
        $message = __('New {0}', Inflector::singularize($entity->getSource()));

        if (!$entity->isNew()) {
            $originalFields = $this->_getOriginalFields($entity, $watchedFields);
            $changedFields = $this->_getChangedFields($entity, $originalFields);

            if (
                $entity->table()->hasBehavior('Timestamp') &&
                count($changedFields) == 1 && !empty($changedFields['modified'])
            ) {
                return; // A not watched field has changed
            }

            $changeAmount = $this->_computeChangeAmount($entity, $originalFields, $changedFields);
            $titleTemplate = '{0} {1} modified';
            $title = __(
                $titleTemplate,
                Inflector::singularize($entity->getSource()),
                $entity->get($this->table()->getDisplayField())
            );
            $message = __n(
                '{0} field was updated',
                '{0} fields were updated',
                $changeAmount,
                $changeAmount
            );
        }
        if ($entity->table()->hasBehavior('MetaFields')) {
            $originalFields['meta_fields'] = $this->_massageMetaField($entity, $originalFields['meta_fields'] ?? []);
            $changedFields['meta_fields'] = $this->_massageMetaField($entity, $changedFields['meta_fields'] ?? []);
            if (empty($originalFields['meta_fields']) && empty($changedFields['meta_fields'])) {
                unset($originalFields['meta_fields']);
                unset($changedFields['meta_fields']);
            }
        }
        $originalFields = $entity->isNew() ? [] : $originalFields;
        $data = [
            'original' => $this->_serializeFields($originalFields),
            'changed' => $this->_serializeFields($changedFields),
            'summaryTemplate' => $titleTemplate,
            'summaryMessage' => $message,
            'entityType' => Inflector::singularize($entity->getSource()),
            'entityDisplayField' => $entity->get($this->table()->getDisplayField()),
            'entityViewURL' => Router::url([
                'controller' => Inflector::underscore($entity->getSource()),
                'action' => 'view',
                $entity->id
            ]),
        ];
        $this->notifySiteAdmins($entity->getSource(), $title, $message, $data);
    }

    public function notifySiteAdminsForEntityDeletion(EntityInterface $entity): void
    {
        $watchedFields = !empty($this->getConfig()['fields']) ? $this->getConfig()['fields'] : $entity->getVisible();
        $originalFields = $entity->extract($watchedFields);
        $changedFields = [];
        $titleTemplate = 'Deleted {0} `{1}`';
        $title = __(
            $titleTemplate,
            Inflector::singularize($entity->getSource()),
            $entity->get($this->table()->getDisplayField())
        );
        $message = __('{0} deleted', Inflector::singularize($entity->getSource()));
        $data = [
            'original' => $this->_serializeFields($originalFields),
            'changed' => $this->_serializeFields($changedFields),
            'summaryTemplate' => $titleTemplate,
            'summaryMessage' => $message,
            'entityType' => Inflector::singularize($entity->getSource()),
            'entityDisplayField' => $entity->get($this->table()->getDisplayField()),
            'entityViewURL' => Router::url([
                'controller' => Inflector::underscore($entity->getSource()),
                'action' => 'view',
                $entity->id
            ]),
        ];
        $this->notifySiteAdmins($entity->getSource(), $title, $message, $data);
    }

    /**
     * Create a message in each site-admin users
     *
     * @param string $title A quick summary of what that notification is about
     * @param string $origin The origin of the notification. For example, could be the model source
     * @param string $message Even more free text
     * @param array $data data used to generate the view when processing.
     *      Must contain: `original` array of the data before the change
     *      Must contain: `changed` array of the data after the change
     *      Optional: `summary` A text summarizing the change
     *      Optional: `summaryTemplate`, `summaryMessage`, `entityType`, `entityDisplayField`, `entityViewURL` text used to build a summary of the change
     * @return void
     */
    public function notifySiteAdmins(
        string $origin,
        string $title,
        string $message,
        array $data
    ): void {
        $this->InboxProcessors = $this->InboxProcessors ?: TableRegistry::getTableLocator()->get('InboxProcessors');
        $processor = $this->InboxProcessors->getProcessor('Notification', 'DataChange');
        $siteAdmins = $this->_getSiteAdmins();
        foreach ($siteAdmins as $siteAdmin) {
            $notificationData = [
                'origin' => $origin,
                'title' => $title,
                'user_id' => $siteAdmin->id,
                'message' => $message,
                'data' => $data,
            ];
            $processor->create($notificationData);
        }
    }

    protected function _getSiteAdmins(): array
    {
        return $this->Users->find()
            ->matching('Roles', function(Query $q) {
                return $q
                    ->where(['Roles.perm_admin' => true]);
            })
            ->all()->toList();
    }

    protected function _getOriginalFields(AppModel $entity, array $fields): array
    {
        $originalChangedFields = $entity->extractOriginalChanged($fields);
        $originalChangedFields = array_map(function ($fieldValue) {
            if (is_array($fieldValue)) {
                return array_filter(array_map(function($fieldValue) {
                    if (is_subclass_of($fieldValue, 'App\Model\Entity\AppModel')) {
                        if (!empty($fieldValue->extractOriginalChanged($fieldValue->getVisible()))) {
                            return $fieldValue;
                        } else {
                            return null;
                        }
                    }
                    return $fieldValue;
                }, $fieldValue), fn ($v) => !is_null($v));
            } else if (is_subclass_of($fieldValue, 'App\Model\Entity\AppModel')) {
                return $fieldValue->extractOriginalChanged($fieldValue);
            }
            return $fieldValue;
        }, $originalChangedFields);
        if ($entity->table()->hasBehavior('MetaFields')) {
            // Include deleted meta-fields
            $originalChangedFields['meta_fields'] = array_merge(
                $originalChangedFields['meta_fields'] ?? [],
                $this->_getDeletedMetafields($entity)
            );
            // Restore original values of meta-fields as the entity has been saved with the changes
            if (!empty($entity->meta_fields)) {
                $originalChangedFields['meta_fields'] = array_map(function ($metaField) {
                    $originalValues = $metaField->getOriginalValues($metaField->getVisible());
                    $originalMetafield = $metaField->table()->newEntity($metaField->toArray());
                    $originalMetafield->set($originalValues);
                    return $originalMetafield;
                }, $originalChangedFields['meta_fields']);
            }
        }
        return $originalChangedFields;
    }

    protected function _getChangedFields(AppModel $entity, array $originalFields): array
    {
        $changedFields =  array_filter(
            $entity->extract(array_keys($originalFields)),
            fn ($v) => !is_null($v)
        );
        if ($entity->table()->hasBehavior('MetaFields')) {
            $changedMetafields = $entity->extractOriginalChanged($entity->getVisible())['meta_fields'] ?? [];
            $changedFields['meta_fields'] = array_filter($changedMetafields, function($metaField) {
                return !empty($metaField->getDirty());
            });
        }
        return $changedFields;
    }

    protected function _massageMetaField(AppModel $entity, array $metaFields): array
    {
        $massaged = [];
        foreach ($metaFields as $metaField) {
            foreach ($entity->MetaTemplates as $template) {
                $templateDisplayName = sprintf('%s (v%s)', $template->name, $template->version);
                foreach ($template['meta_template_fields'] as $field) {
                    if ($metaField->meta_template_id == $template->id && $metaField->meta_template_field_id == $field->id) {
                        if (!empty($massaged[$templateDisplayName][$field['field']])) {
                            if (!is_array($massaged[$templateDisplayName][$field['field']])) {
                                $massaged[$templateDisplayName][$field['field']] = [$massaged[$templateDisplayName][$field['field']]];
                            }
                            $massaged[$templateDisplayName][$field['field']][] = $metaField['value'];
                        } else {
                            $massaged[$templateDisplayName][$field['field']] = $metaField['value'];
                        }
                        break 2;
                    }
                }
            }
        }
        return $massaged;
    }

    protected function _getDeletedMetafields(AppModel $entity): array
    {
        return $entity->_metafields_to_delete ?? [];
    }

    protected function _computeChangeAmount(AppModel $entity, array $originalFields, array $changedFields): int
    {
        $amount = count($changedFields);
        if ($entity->table()->hasBehavior('MetaFields')) {
            $amount -= 1; // `meta_fields` key was counted without checking at the content
        }
        if (!empty($originalFields['meta_fields']) && !empty($changedFields['meta_fields'])) {
            $amount += count(array_intersect_key($originalFields['meta_fields'] ?? [], $changedFields['meta_fields'] ?? [])); // Add changed fields
            $amount += count(array_diff_key($changedFields['meta_fields'] ?? [], $originalFields['meta_fields'] ?? [])); // Add new fields
            $amount += count(array_diff_key($originalFields['meta_fields'] ?? [], $changedFields['meta_fields'] ?? [])); // Add deleted fields
        }
        return $amount;
    }

    protected function _serializeFields($fields): array
    {
        return array_map(function ($fieldValue) {
            if (is_bool($fieldValue)) {
                return empty($fieldValue) ? 0 : 1;
            } else if (is_array($fieldValue)) {
                return json_encode($fieldValue);
            } else if (is_object($fieldValue)) {
                switch (get_class($fieldValue)) {
                    case 'Cake\I18n\FrozenTime':
                        return $fieldValue->i18nFormat('yyyy-mm-dd HH:mm:ss');
                }
            } else {
                return strval($fieldValue);
            }
        }, $fields);
    }
}
