<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Error\Debugger;

class OrganisationsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->addBehavior('Timestamp');
        $this->addBehavior('Tags.Tag');
        $this->addBehavior('AuditLog');
        $this->addBehavior('NotifyAdmins', [
            'fields' => ['uuid', 'name', 'url', 'nationality', 'sector', 'type', 'contacts', 'modified', 'meta_fields'],
        ]);
        $this->hasMany(
            'Alignments',
            [
                'dependent' => true,
                'cascadeCallbacks' => true
            ]
        );
        $this->hasMany(
            'EncryptionKeys',
            [
                'dependent' => true,
                'foreignKey' => 'owner_id',
                'conditions' => ['owner_model' => 'organisation']
            ]
        );
        $this->belongsToMany('OrgGroups', [
            'joinTable' => 'org_groups_organisations',
        ]);
        $this->addBehavior('MetaFields');
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

    public function captureOrg($org): ?int
    {
        if (!empty($org['uuid'])) {
            $existingOrg = $this->find()->where([
                'uuid' => $org['uuid']
            ])->first();
        } else {
            return null;
        }
        if (empty($existingOrg)) {
            $entityToSave = $this->newEmptyEntity();
            $this->patchEntity($entityToSave, $org, [
                'accessibleFields' => $entityToSave->getAccessibleFieldForNew()
            ]);
        } else {
            $this->patchEntity($existingOrg, $org);
            $entityToSave = $existingOrg;
        }
        $entityToSave->setDirty('modified', false);
        $savedEntity = $this->save($entityToSave, ['associated' => false]);
        if (!$savedEntity) {
            return null;
        }
        $this->postCaptureActions($savedEntity->id, $org);
        return $savedEntity->id;
    }

    public function postCaptureActions($id, $org)
    {
        if (!empty($org['metaFields'])) {
            $this->saveMetaFields($id, $org);
        }
    }

    public function getEditableOrganisationsForUser($user): array
    {
        $query = $this->find();
        if (empty($user['role']['perm_community_admin'])) {
            if (!empty($user['role']['perm_org_admin'])) {
                $query->where(['Organisations.id' => $user['organisation']['id']]);
            } else {
                return []; // User not an org_admin. Cannot edit orgs
            }
        }
        return $query->all()->toList();
    }
}
