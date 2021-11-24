<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Error\Debugger;

class OrganisationsTable extends AppTable
{
    public $metaFields = 'organisation';

    protected $_accessible = [
        'id' => false
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('Timestamp');
        $this->addBehavior('Tags.Tag');
        $this->addBehavior('AuditLog');
        $this->addBehavior('UUID');
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
        $this->hasMany(
            'MetaFields',
            [
                'dependent' => true,
                'foreignKey' => 'parent_id',
                'conditions' => ['MetaFields.scope' => 'organisation']
            ]
        );
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
}
