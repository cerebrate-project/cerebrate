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
        foreach ($input['metaFields'] as $metaField => $values) {
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
                    $this->MetaFields->save($temp);
                }
            }
        }
    }
}
