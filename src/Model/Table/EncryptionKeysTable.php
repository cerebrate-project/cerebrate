<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use ArrayObject;

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
                'conditions' => ['owner_model' => 'individual']
            ]
        );
        $this->belongsTo(
            'Organisations',
            [
                'foreignKey' => 'owner_id',
                'conditions' => ['owner_model' => 'organisation']
            ]
        );
        $this->setDisplayField('encryption_key');
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        if (empty($data['owner_id'])) {
            if (empty($data['owner_model'])) {
                return false;
            }
            if (empty($data[$data['owner_model'] . '_id'])) {
                return false;
            }
            $data['owner_id'] = $data[$data['owner_model'] . '_id'];
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('type')
            ->notEmptyString('encryption_key')
            ->notEmptyString('owner_id')
            ->notEmptyString('owner_model')
            ->requirePresence(['type', 'encryption_key', 'owner_id', 'owner_model'], 'create');
        return $validator;
    }
}
