<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class IndividualsTable extends AppTable
{
    public $metaFields = 'individual';

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->addBehavior('Timestamp');
        $this->addBehavior('Tags.Tag');
        $this->addBehavior('AuditLog');
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
                'foreignKey' => 'owner_id',
                'conditions' => ['owner_model' => 'individual']
            ]
        );
        $this->hasOne(
            'Users'
        );
        $this->belongsToMany('Organisations', [
            'through' => 'Alignments',
        ]);
        $this->setDisplayField('email');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('email')
            ->requirePresence(['email'], 'create');
        return $validator;
    }

    public function captureIndividual($individual): ?int
    {
        if (!empty($individual['uuid'])) {
            $existingIndividual = $this->find()->where([
                'uuid' => $individual['uuid']
            ])->first();
        } else {
            return null;
        }
        if (empty($existingIndividual)) {
            $entityToSave = $this->newEmptyEntity();
            $this->patchEntity($entityToSave, $individual, [
                'accessibleFields' => $entityToSave->getAccessibleFieldForNew()
            ]);
        } else {
            $this->patchEntity($existingIndividual, $individual);
            $entityToSave = $existingIndividual;
        }
        $entityToSave->setDirty('modified', false);
        $savedEntity = $this->save($entityToSave, ['associated' => false]);
        if (!$savedEntity) {
            return null;
        }
        $this->postCaptureActions($savedEntity);
        return $savedEntity->id;
    }

    public function postCaptureActions($individual): void
    {
        if (!empty($individual['metaFields'])) {
            $this->saveMetaFields($id, $individual);
        }
        if (!empty($individual['alignments'])) {
            $Organisation = \Cake\ORM\TableRegistry::getTableLocator()->get('Organisations');
            foreach ($individual['alignments'] as $alignment) {
                $org_id = $Organisation->captureOrg($alignment['organisation']);
                if ($org_id) {
                    $this->Alignments->setAlignment($org_id, $individual->id, $alignment['type']);
                }
            }
        }
    }
}
