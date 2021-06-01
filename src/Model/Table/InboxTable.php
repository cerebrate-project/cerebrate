<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type;
use Cake\ORM\Table;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

Type::map('json', 'Cake\Database\Type\JsonType');

class InboxTable extends AppTable
{

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new'
                ]
            ]
        ]);

        $this->belongsTo('Users');
        $this->setDisplayField('title');
    }

    protected function _initializeSchema(TableSchemaInterface $schema): TableSchemaInterface
    {
        $schema->setColumnType('data', 'json');

        return $schema;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('scope')
            ->notEmptyString('action')
            ->notEmptyString('title')
            ->notEmptyString('origin')
            ->datetime('created')
 
            ->requirePresence([
                'scope' => ['message' => __('The field `scope` is required')],
                'action' => ['message' => __('The field `action` is required')],
                'title' => ['message' => __('The field `title` is required')],
                'origin' => ['message' => __('The field `origin` is required')],
            ], 'create');
        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('user_id', 'Users'), [
            'message' => 'The provided `user_id` does not exist'
        ]);

        return $rules;
    }
}
