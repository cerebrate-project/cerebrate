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
                'conditions' => ['owner_type' => 'individual']
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
            $entity = $this->newEntity($individual, ['associated' => []]);
            if (!$this->save($entity)) {
                return null;
            }
            $individual = $entity;
        } else {
            $reserved = ['id', 'uuid', 'metaFields'];
            foreach ($individual as $field => $value) {
                if (in_array($field, $reserved)) {
                    continue;
                }
                $existingIndividual->$field = $value;
            }
            if (!$this->save($existingIndividual, ['associated' => false])) {
                return null;
            }
            $individual = $existingIndividua;
        }
        $this->postCaptureActions($individual);
        return $individual->id;
    }

    public function postCaptureActions($individual): void
    {
        if (!empty($individual['metaFields'])) {
            $this->saveMetaFields($id, $individual);
        }
        if (!empty($individual['alignments'])) {
            foreach ($individual['alignments'] as $alignment) {
                $org_id = $this->Organisation->captureOrg($alignment['organisation']);
                if ($org_id) {
                    $this->Alignments->setAlignment($org_id, $individual->id, $alignment['type']);
                }
            }
        }
    }
}
