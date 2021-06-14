<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Filesystem\Folder;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Core\Exception\Exception;

class MissingRequestProcessorException extends Exception
{
    protected $_defaultCode = 404;
}

class RequestProcessorTable extends AppTable
{
    private $processorsDirectory = ROOT . '/libraries/default/RequestProcessors';
    private $requestProcessors;
    private $enabledProcessors = [ // to be defined in config
        'Brood' => [
            'ToolInterconnection' => false,
            'OneWaySynchronization' => false,
        ],
        'Proposal' => [
            'ProposalEdit' => false,
        ],
        'Synchronisation' => [
            'DataExchange' => false,
        ],
        'User' => [
            'Registration' => true,
        ],
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->loadProcessors();
    }

    public function getProcessor($scope, $action=null)
    {
        if (isset($this->requestProcessors[$scope])) {
            if (is_null($action)) {
                return $this->requestProcessors[$scope];
            } else if (!empty($this->requestProcessors[$scope]->{$action})) {
                return $this->requestProcessors[$scope]->{$action};
            } else {
                throw new \Exception(__('Processor {0}.{1} not found', $scope, $action));
            }
        }
        throw new MissingRequestProcessorException(__('Processor not found'));
    }

    public function getLocalToolProcessor($action, $connectorName)
    {
        $scope = "LocalTool";
        $specificScope = "{$connectorName}LocalTool";
        try { // try to get specific processor for module name or fall back to generic local tool processor
            $processor = $this->getProcessor($specificScope, $action);
        } catch (MissingRequestProcessorException $e) {
            $processor = $this->getProcessor($scope, $action);
        }
        return $processor;
    }

    public function listProcessors($scope=null)
    {
        if (is_null($scope)) {
            return $this->requestProcessors;
        } else {
            if (isset($this->requestProcessors[$scope])) {
                return $this->requestProcessors[$scope];
            } else {
                throw new MissingRequestProcessorException(__('Processors for {0} not found', $scope));
            }
        }
    }

    private function loadProcessors()
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
            if (is_object($processorMainClass)) {
                $this->requestProcessors[$processorMainClassNameShort] = $processorMainClass;
                foreach ($this->requestProcessors[$processorMainClassNameShort]->getRegisteredActions() as $registeredAction) {
                    $scope = $this->requestProcessors[$processorMainClassNameShort]->getScope();
                    if (!empty($this->enabledProcessors[$scope][$registeredAction])) {
                        $this->requestProcessors[$processorMainClassNameShort]->{$registeredAction}->enabled = true;
                    } else {
                        $this->requestProcessors[$processorMainClassNameShort]->{$registeredAction}->enabled = false;
                    }
                }
            } else {
                $this->requestProcessors[$processorMainClassNameShort] = new \stdClass();
                $this->requestProcessors[$processorMainClassNameShort]->{$registeredAction} = new \stdClass();
                $this->requestProcessors[$processorMainClassNameShort]->{$registeredAction}->action = "N/A";
                $this->requestProcessors[$processorMainClassNameShort]->{$registeredAction}->enabled = false;
                $this->requestProcessors[$processorMainClassNameShort]->{$registeredAction}->error = $processorMainClass;
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
     * createInboxEntry
     *
     * @param  Object|Array $processor can either be the processor object or an array containing data to fetch it
     * @param  Array $data
     * @return Array
     */
    public function createInboxEntry($processor, $data)
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
