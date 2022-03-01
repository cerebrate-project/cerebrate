<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;

use MetaFieldsTypes\TextType;
use MetaFieldsTypes\IPv4Type;
use MetaFieldsTypes\IPv6Type;

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
