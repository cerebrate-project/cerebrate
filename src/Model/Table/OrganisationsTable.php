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
                'conditions' => ['owner_type' => 'organisation']
            ]
        );
        $this->hasMany(
            'MetaFields',
            [
                'dependent' => true,
                'foreignKey' => 'parent_id',
                'conditions' => ['scope' => 'organisation']
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
        if (!empty($org['id'])) {
            unset($org['id']);
        }
        if (!empty($org['uuid'])) {
            $existingOrg = $this->find()->where([
                'uuid' => $org['uuid']
            ])->first();
        } else {
            return null;
        }
        if (empty($existingOrg)) {
            $data = $this->newEmptyEntity();
            $data = $this->patchEntity($data, $org, ['associated' => []]);
            if (!$this->save($data)) {
                return null;
            }
            $savedOrg = $data;
        } else {
            $reserved = ['id', 'uuid', 'metaFields'];
            foreach ($org as $field => $value) {
                if (in_array($field, $reserved)) {
                    continue;
                }
                $existingOrg->$field = $value;
            }
            if (!$this->save($existingOrg)) {
                return null;
            }
            $savedOrg = $existingOrg;
        }
        $this->postCaptureActions($savedOrg->id, $org);
        return $savedOrg->id;
    }

    public function postCaptureActions($id, $org)
    {
        if (!empty($org['metaFields'])) {
            $this->saveMetaFields($id, $org);
        }
    }
}
