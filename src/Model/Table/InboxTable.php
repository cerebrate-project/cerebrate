<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type;
use Cake\Filesystem\Folder;
use Cake\ORM\Table;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

Type::map('json', 'Cake\Database\Type\JsonType');

class InboxTable extends AppTable
{
    private $processorsDirectory = ROOT . '/libraries/RequestProcessors';
    private $requestProcessors;

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

    public function getRequestProcessor($name, $action=null)
    {
        if (!isset($this->requestProcessors)) {
            $this->loadRequestProcessors();
        }
        if (isset($this->requestProcessors[$name])) {
            if (is_null($action)) {
                return $this->requestProcessors[$name];
            } else if (!empty($this->requestProcessors[$name]->{$action})) {
                return $this->requestProcessors[$name]->{$action};
            } else {
                throw new \Exception(__('Processor {0}.{1} not found', $name, $action));
            }
        }
        throw new \Exception(__('Processor not found'), 1);
    }

    private function loadRequestProcessors()
    {
        $processorDir = new Folder($this->processorsDirectory);
        $processorFiles = $processorDir->find('.*RequestProcessor\.php', true);
        foreach ($processorFiles as $processorFile) {
            if ($processorFile == 'GenericRequestProcessor.php') {
                continue;
            }
            $processorMainClassName = str_replace('.php', '', $processorFile);
            $processorMainClassNameShort = str_replace('RequestProcessor.php', '', $processorFile);
            $processorMainClass = $this->getProcessorClass($processorDir->pwd() . DS . $processorFile, $processorMainClassName);
            if ($processorMainClass !== false) {
                $this->requestProcessors[$processorMainClassNameShort] = $processorMainClass;
            }
        }
    }

    private function getProcessorClass($filePath, $processorMainClassName)
    {
        require_once($filePath);
        $reflection = new \ReflectionClass($processorMainClassName);
        $processorMainClass = $reflection->newInstance(true);
        if ($processorMainClass->checkLoading() === 'Assimilation successful!') {
            return $processorMainClass;
        }
        try {
        } catch (Exception $e) {
            return false;
        }
    }
}
