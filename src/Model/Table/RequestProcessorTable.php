<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Filesystem\Folder;

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
        throw new \Exception(__('Processor not found'), 1);
    }

    public function listProcessors($scope=null)
    {
        if (is_null($scope)) {
            return $this->requestProcessors;
        } else {
            if (isset($this->requestProcessors[$scope])) {
                return $this->requestProcessors[$scope];
            } else {
                throw new \Exception(__('Processors for {0} not found', $scope));
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
            if ($processorMainClass !== false) {
                $this->requestProcessors[$processorMainClassNameShort] = $processorMainClass;
                foreach ($this->requestProcessors[$processorMainClassNameShort]->getRegisteredActions() as $registeredAction) {
                    $scope = $this->requestProcessors[$processorMainClassNameShort]->getScope();
                    if (!empty($this->enabledProcessors[$scope][$registeredAction])) {
                        $this->requestProcessors[$processorMainClassNameShort]->{$registeredAction}->enabled = true;
                    } else {
                        $this->requestProcessors[$processorMainClassNameShort]->{$registeredAction}->enabled = false;
                    }
                }
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
    }
}
