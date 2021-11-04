<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class MetaFieldsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->addBehavior('Timestamp');
        $this->addBehavior('CounterCache', [
            'MetaTemplateFields' => ['counter']
        ]);

        $this->belongsTo('MetaTemplates');
        $this->belongsTo('MetaTemplateFields');

        $this->setDisplayField('field');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('scope')
            ->notEmptyString('field')
            ->notEmptyString('uuid')
            ->notEmptyString('value')
            ->notEmptyString('meta_template_id')
            ->notEmptyString('meta_template_field_id')
            ->requirePresence(['scope', 'field', 'value', 'uuid', 'meta_template_id', 'meta_template_field_id'], 'create');

        // add validation regex
        return $validator;
    }
}
