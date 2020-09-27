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
        $this->setDisplayField('field');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('field')
            ->notEmptyString('type')
            ->numeric('meta_template_id')
            ->notBlank('meta_template_id')
            ->requirePresence(['meta_template_id', 'field', 'type'], 'create');
        return $validator;
    }
}
