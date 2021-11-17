<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Filesystem\Folder;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Core\Exception\Exception;

class MissingOutboxProcessorException extends Exception
{
    protected $_defaultCode = 404;
}

class OutboxProcessorsTable extends AppTable
{
    private $processorsDirectory = ROOT . '/libraries/default/OutboxProcessors';
    private $outboxProcessors;
    private $enabledProcessors = [ // to be defined in config
        'Brood' => [
            'ResendFailedMessageProcessor' => true,
        ],
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        if (empty($this->outboxProcessors)) {
            $this->loadProcessors();
        }
        $this->addBehavior('AuditLog');
    }

    public function getProcessor($scope, $action=null)
    {
        if (isset($this->outboxProcessors[$scope])) {
            if (is_null($action)) {
                return $this->outboxProcessors[$scope];
            } else if (!empty($this->outboxProcessors[$scope]->{$action})) {
                return $this->outboxProcessors[$scope]->{$action};
            } else {
                throw new \Exception(__('Processor {0}.{1} not found', $scope, $action));
            }
        }
        throw new MissingOutboxProcessorException(__('Processor not found'));
    }

    public function listProcessors($scope=null)
    {
        if (is_null($scope)) {
            return $this->outboxProcessors;
        } else {
            if (isset($this->outboxProcessors[$scope])) {
                return $this->outboxProcessors[$scope];
            } else {
                throw new MissingOutboxProcessorException(__('Processors for {0} not found', $scope));
            }
        }
    }

    private function loadProcessors()
    {
        $processorDir = new Folder($this->processorsDirectory);
        $processorFiles = $processorDir->find('.*OutboxProcessor\.php', true);
        foreach ($processorFiles as $processorFile) {
            if ($processorFile == 'GenericOutboxProcessor.php') {
                continue;
            }
            $processorMainClassName = str_replace('.php', '', $processorFile);
            $processorMainClassNameShort = str_replace('OutboxProcessor.php', '', $processorFile);
            $processorMainClass = $this->getProcessorClass($processorDir->pwd() . DS . $processorFile, $processorMainClassName);
            if (is_object($processorMainClass)) {
                $this->outboxProcessors[$processorMainClassNameShort] = $processorMainClass;
                foreach ($this->outboxProcessors[$processorMainClassNameShort]->getRegisteredActions() as $registeredAction) {
                    $scope = $this->outboxProcessors[$processorMainClassNameShort]->getScope();
                    if (!empty($this->enabledProcessors[$scope][$registeredAction])) {
                        $this->outboxProcessors[$processorMainClassNameShort]->{$registeredAction}->enabled = true;
                    } else {
                        $this->outboxProcessors[$processorMainClassNameShort]->{$registeredAction}->enabled = false;
                    }
                }
            } else {
                $this->outboxProcessors[$processorMainClassNameShort] = new \stdClass();
                $this->outboxProcessors[$processorMainClassNameShort]->{$registeredAction} = new \stdClass();
                $this->outboxProcessors[$processorMainClassNameShort]->{$registeredAction}->action = "N/A";
                $this->outboxProcessors[$processorMainClassNameShort]->{$registeredAction}->enabled = false;
                $this->outboxProcessors[$processorMainClassNameShort]->{$registeredAction}->error = $processorMainClass;
            }
        }
    }

    /**
     * getProcessorClass
     *
     * @param  string $filePath
     * @param  string $processorMainClassName
     * @return object|string Object loading success, string containing the error if failure
     */
    private function getProcessorClass($filePath, $processorMainClassName)
    {
        try {
            require_once($filePath);
            try {
                $reflection = new \ReflectionClass($processorMainClassName);
            } catch (\ReflectionException $e) {
                return $e->getMessage();
            }
            $processorMainClass = $reflection->newInstance(true);
            if ($processorMainClass->checkLoading() === 'Assimilation successful!') {
                return $processorMainClass;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * createOutboxEntry
     *
     * @param  Object|Array $processor can either be the processor object or an array containing data to fetch it
     * @param  Array $data
     * @return Array
     */
    public function createOutboxEntry($processor, $data)
    {
        if (!is_object($processor) && !is_array($processor)) {
            throw new MethodNotAllowedException(__("Invalid processor passed"));
        }
        if (is_array($processor)) {
            if (empty($processor['scope']) || empty($processor['action'])) {
                throw new MethodNotAllowedException(__("Invalid data passed. Missing either `scope` or `action`"));
            }
            $processor = $this->getProcessor('User', 'Registration');
        }
        return $processor->create($data);
    }
}
