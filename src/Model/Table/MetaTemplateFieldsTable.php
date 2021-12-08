<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class MetaTemplateFieldsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->BelongsTo(
            'MetaTemplates'
        );
        $this->hasMany('MetaFields');

        $this->setDisplayField('field');
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
}
