<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Utility\Text;
use ArrayObject;

class EnumerationCollectionsTable extends AppTable
{
    private $fieldMapping = [
        'Organisations' => [
            'country',
            'sector',
            'type'
        ]
    ];
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->addBehavior('AuditLog');
        $this->addBehavior('Timestamp');
        $this->hasMany(
            'Enumerations',
            [
                'dependent' => true
            ]
        );
        $this->setDisplayField('name');
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        if (empty($data['uuid'])) {
            $data['uuid'] = Text::uuid();
        }
        return true;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('name')
            ->requirePresence(['name'], 'create')
            ->notEmptyString('uuid')
            ->requirePresence(['uuid'], 'create')
            ->notEmptyString('target_model')
            ->requirePresence(['target_model'], 'create')
            ->notEmptyString('target_field')
            ->requirePresence(['target_field'], 'create');
        return $validator;
    }

    public function getValidFieldList(?string $model = null): array
    {
        if (!empty($model)) {
            if (empty($this->fieldMapping[$model])) {
                return [];
            } else {
                return $this->fieldMapping[$model];
            }
        } else {
            return $this->fieldMapping;
        }
    }

    public function getValidModelList(?string $model = null): array
    {
        
        return array_keys($this->fieldMapping);
    }

    public function getFieldValues($model): array
    {
        $collections = $this->find('all')->where(['target_model' => $model, 'enabled' => 1, 'deleted' => 0])->contain(['Enumerations'])->disableHydration()->all()->toArray();
        $options = [];
        foreach ($collections as $collection) {
            if (empty($collection['target_field'])) {
                $options[$collection['target_field']] = [];
            }
            foreach ($collection['enumerations'] as $enumeration) {
                $options[$collection['target_field']][$enumeration['value']] = $enumeration['value'];
            }
        }
        return $options;
    }

    public function purgeValues(\App\Model\Entity\EnumerationCollection $entity): void
    {
        $this->Enumerations->deleteAll([
            'enumeration_collection_id' => $entity->id
        ]);
    }

    public function captureValues(\App\Model\Entity\EnumerationCollection $entity): void
    {
        if (!empty($entity->values)) {
            $values = $entity->values;
            $collection_id = $entity->id;
            if (!is_array($values)) {
                $values = explode("\n", $values);
            }
            foreach ($values as $value) {
                $enumeration = $this->Enumerations->newEntity([
                    'value' => trim($value),
                    'enumeration_collection_id' => $entity->id
                ]);
                $this->Enumerations->save($enumeration);
            }
        }
    }
}
