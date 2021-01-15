<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\ORM\TableRegistry;

class AppTable extends Table
{
    public function initialize(array $config): void
    {
    }

    public function saveMetaFields($id, $input)
    {
        $this->MetaFields = TableRegistry::getTableLocator()->get('MetaFields');
        $this->MetaTemplates = TableRegistry::getTableLocator()->get('MetaTemplates');
        foreach ($input['metaFields'] as $templateID => $metaFields) {
            $metaTemplates = $this->MetaTemplates->find()->where([
                'id' => $templateID,
                'enabled' => 1
            ])->contain(['MetaTemplateFields'])->first();
            $fieldNameToId = [];
            foreach ($metaTemplates->meta_template_fields as $i => $metaTemplateField) {
                $fieldNameToId[$metaTemplateField->field] = $metaTemplateField->id;
            }
            foreach ($metaFields as $metaField => $values) {
                if (!is_array($values)) {
                    $values = [$values];
                }
                foreach ($values as $value) {
                    if ($value !== '') {
                        $temp = $this->MetaFields->newEmptyEntity();
                        $temp->field = $metaField;
                        $temp->value = $value;
                        $temp->scope = $this->metaFields;
                        $temp->parent_id = $id;
                        $temp->meta_template_id = $templateID;
                        $temp->meta_template_field_id = $fieldNameToId[$metaField];
                        $res = $this->MetaFields->save($temp);
                    }
                }
            }
        }
    }
}
