<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class EncryptionKeysTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->belongsTo(
            'Individuals',
            [
                'foreignKey' => 'owner_id',
                'conditions' => ['owner_type' => 'individual']
            ]
        );
        $this->belongsTo(
            'Organisations',
            [
                'foreignKey' => 'owner_id',
                'conditions' => ['owner_type' => 'organisation']
            ]
        );
        $this->setDisplayField('encryption_key');
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['owner_id'])) {
            if (empty($data['owner_type']) || !in_array(['individual', 'organisation'], $data['owner_type'])) {
                return false;
            }
            $data[$data['owner_type'] . '_id'] = $data['owner_id'];
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('type')
            ->notEmptyString('encryption_key')
            ->notEmptyString('owner_id')
            ->notEmptyString('owner_type')
            ->requirePresence(['type', 'encryption_key', 'owner_id', 'owner_type'], 'create');
        return $validator;
    }
}
