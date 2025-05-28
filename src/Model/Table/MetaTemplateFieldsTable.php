<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;

use MetaFieldsTypes\TextType;
use MetaFieldsTypes\IPv4Type;
use MetaFieldsTypes\IPv6Type;
require_once(APP . 'Lib' . DS . 'default' . DS . 'meta_fields_types' . DS . 'TextType.php');
require_once(APP . 'Lib' . DS . 'default' . DS . 'meta_fields_types' . DS . 'IPv4Type.php');
require_once(APP . 'Lib' . DS . 'default' . DS . 'meta_fields_types' . DS . 'IPv6Type.php');

class MetaTemplateFieldsTable extends AppTable
{
    private $typeHandlers = [];

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->BelongsTo(
            'MetaTemplates'
        );
        $this->hasMany('MetaFields');

        $this->setDisplayField('field');
        $this->getSchema()->setColumnType('sane_default', 'json');
        $this->getSchema()->setColumnType('values_list', 'json');
        $this->loadTypeHandlers();
    }

    public function beforeSave($event, $entity, $options)
    {
        if (empty($entity->meta_template_id)) {
            $event->stopPropagation();
            $event->setResult(false);
            return;
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('field')
            ->notEmptyString('type')
            ->requirePresence(['field', 'type'], 'create');

        $jsonArrayRule = [
            'rule' => function ($value, $context) {
                if ($value === null || $value === '' || is_array($value)) {
                    return true;
                }

                $decoded = json_decode($value, true);
                return is_array($decoded) && array_keys($decoded) === range(0, count($decoded) - 1);
            },
            'message' => 'This field must be a valid JSON array.'
        ];

        $validator->add('sane_default', 'validJsonArray', $jsonArrayRule);
        $validator->add('values_list', 'validJsonArray', $jsonArrayRule);

        return $validator;
    }

    public function loadTypeHandlers(): void
    {
        if (empty($this->typeHandlers)) {
            $typeHandlers = [
                new TextType(),
                new IPv4Type(),
                new IPv6Type(),
            ];
            foreach ($typeHandlers as $handler) {
                $this->typeHandlers[$handler::TYPE] = $handler;
            }
        }
    }

    public function getTypeHandlers(): array
    {
        return $this->typeHandlers;
    }

    public function getTypeHandler($type)
    {
        return $this->typeHandlers[$type] ?? false;
    }
}
