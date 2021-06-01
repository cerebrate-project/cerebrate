<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Migrations\Migrations;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
use Cake\Http\Exception\NotFoundException;

class LocalToolsTable extends AppTable
{

    const HEALTH_CODES = [
        0 => 'UNKNOWN',
        1 => 'OK',
        2 => 'ISSUES',
        3 => 'ERROR',
    ];

    public $exposedFunctions = [];

    private $currentConnector = null;

    private $connectors = null;

    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator;
    }

    public function loadConnector(string $connectorName): void
    {
        if (empty($currentConnector) || get_class($currentConnector) !== $connectorName) {
            $connectors = $this->getConnectors($connectorName);
            if (empty($connectors[$connectorName])) {
                throw new NotFoundException(__('Invalid connector module requested.'));
            } else {
                $this->currentConnector = $connectors[$connectorName];
            }
        }
    }

    public function action(int $user_id, string $connectorName, string $actionName, array $params, \Cake\Http\ServerRequest $request): array
    {
        $this->loadConnector($connectorName);
        $params['request'] = $request;
        $params['user_id'] = $user_id;
        return $this->currentConnector->{$actionName}($params);
    }

    public function getActionDetails(string $actionName): array
    {
        if (!empty($this->currentConnector->exposedFunctions[$actionName])) {
            return $this->currentConnector->exposedFunctions[$actionName];
        }
        throw new NotFoundException(__('Invalid connector module action requested.'));
    }

    public function getActionFilterOptions(string $connectorName, string $actionName): array
    {
        $this->loadConnector($connectorName);
        if (!empty($this->currentConnector->exposedFunctions[$actionName])) {
            return $this->currentConnector->exposedFunctions[$actionName]['params'] ?? [];
        }
        throw new NotFoundException(__('Invalid connector module action requested.'));
    }

    public function getConnectors(string $name = null): array
    {
        $connectors = [];
        $dirs = [
            ROOT . '/src/Lib/default/local_tool_connectors',
            ROOT . '/src/Lib/custom/local_tool_connectors'
        ];
        foreach ($dirs as $dir) {
            $dir = new Folder($dir);
            $files = $dir->find('.*Connector\.php');
            foreach ($files as $file) {
                require_once($dir->pwd() . '/'. $file);
                $className = substr($file, 0, -4);
                $classNamespace = '\\' . $className . '\\' . $className;
                if (empty($name) || $name === $className) {
                    $connectors[$className] = new $classNamespace;
                }
            }
        }
        return $connectors;
    }

    public function extractMeta(array $connector_classes, bool $includeConnections = false): array
    {
        $connectors = [];
        foreach ($connector_classes as $connector_type => $connector_class) {
            $connector = [
                'name' => $connector_class->name,
                'connector' => $connector_type,
                'connector_version' => $connector_class->version,
                'connector_description' => $connector_class->description
            ];
            if ($includeConnections) {
                $connector['connections'] = $this->healthCheck($connector_type, $connector_class);
            }
            $connectors[] = $connector;
        }
        return $connectors;
    }

    public function healthCheck(string $connector_type, Object $connector_class): array
    {
        $query = $this->find();
        $query->where([
            'connector' => $connector_type
        ]);
        $connections = $query->all()->toList();
        foreach ($connections as &$connection) {
            $connection = $this->healthCheckIndividual($connection);
        }
        return $connections;
    }

    public function healthCheckIndividual(Object $connection): array
    {
        $connector_class = $this->getConnectors($connection->connector);
        if (empty($connector_class[$connection->connector])) {
            return [];
        }
        $connector_class = $connector_class[$connection->connector];
        $health = $connector_class->health($connection);
        return $connection = [
            'name' => $connection->name,
            'health' => $health['status'],
            'message' => $health['message'],
            'url' => '/localTools/view/' . $connection['id']
        ];
    }

    public function getConnectorByConnectionId($id): array
    {
        $connection = $this->find()->where(['id' => $id])->first();
        if (empty($connection)) {
            throw new NotFoundException(__('Invalid connection.'));
        }
        return $this->getConnectors($connection->connector);
    }

    public function getChildParameters($id): array
    {
        $connectors = $this->getConnectorByConnectionId($id);
        if (empty($connectors)) {
            throw new NotFoundException(__('Invalid connector.'));
        }
        $connector = array_values($connectors)[0];
        $children = [];
        foreach ($connector->exposedFunctions as $functionName => $function) {
            if ($function['type'] === 'index') {
                $children[] = $functionName;
            }
        }
        return $children;
    }
}
