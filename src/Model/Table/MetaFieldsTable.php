<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use ArrayObject;

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

        $this->addBehavior('AuditLog');
        $this->addBehavior('Timestamp');
        $this->belongsTo('MetaTemplates');
        $this->belongsTo('MetaTemplateFields');
        $this->belongsTo('MetaTemplateNameDirectory')
            ->setForeignKey('meta_template_directory_id');

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
            // ->requirePresence(['scope', 'field', 'value', 'uuid', 'meta_template_id', 'meta_template_field_id'], 'create');
            // ->requirePresence(['scope', 'field', 'value', 'uuid',], 'create');
            ->notEmptyString('meta_template_directory_id')
            ->requirePresence(['scope', 'field', 'value', 'uuid', 'meta_template_directory_id', ], 'create');

        $validator->add('value', 'validMetaField', [
            'rule' => 'isValidMetaField',
            'message' => __('The provided value doesn\'t pass the validation check for its meta-template.'),
            'provider' => 'table',
        ]);

        return $validator;
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        if (!isset($data['meta_template_directory_id'])) {
            $data['meta_template_directory_id'] = $this->getTemplateDirectoryIdFromMetaTemplate($data['meta_template_id']);
        }
    }

    public function getTemplateDirectoryIdFromMetaTemplate($metaTemplateId): int
    {
        return $this->MetaTemplates->find()
            ->select('meta_template_directory_id')
            ->where(['id' => $metaTemplateId])
            ->first();
    }

    public function isValidMetaField($value, array $context)
    {
        $metaFieldsTable = $context['providers']['table'];
        $entityData = $context['data'];
        if (empty($entityData['meta_template_field_id'])) {
            return true;
        }
        $metaTemplateField = $metaFieldsTable->MetaTemplateFields->get($entityData['meta_template_field_id']);
        return $this->isValidMetaFieldForMetaTemplateField($value, $metaTemplateField);
    }

    public function isValidMetaFieldForMetaTemplateField($value, $metaTemplateField)
    {
        $typeValid = $this->isValidType($value, $metaTemplateField);
        if ($typeValid !== true) {
            return $typeValid;
        }
        if (!empty($metaTemplateField['regex'])) {
            return $this->isValidRegex($value, $metaTemplateField);
        }
        return true;
    }

    public function isValidType($value, $metaTemplateField)
    {
        if (empty($value)) {
            return __('Metafield value cannot be empty.');
        }
        $typeHandler = $this->MetaTemplateFields->getTypeHandler($metaTemplateField['type']);
        if (!empty($typeHandler)) {
            $success = $typeHandler->validate($value);
            return $success ? true : __('Metafields value `{0}` for `{1}` doesn\'t pass type validation.', $value, $metaTemplateField['field']);
        }
        /*
            We should not end-up in this case. But if someone creates a new type without his handler,
            we consider its type to be a valid text.
        */
        return true;
    }

    public function isValidRegex($value, $metaTemplateField)
    {

        $re = $metaTemplateField['regex'];
        if (!preg_match("/^$re$/m", $value)) {
            return __('Metafield value `{0}` for `{1}` doesn\'t pass regex validation.', $value, $metaTemplateField['field']);
        }
        return true;
    }
}
