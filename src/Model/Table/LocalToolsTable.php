<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Migrations\Migrations;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

class LocalToolsTable extends AppTable
{

    const HEALTH_CODES = [
        0 => 'UNKNOWN',
        1 => 'OK',
        2 => 'ISSUES',
        3 => 'ERROR',
    ];

    private $connectors = null;

    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator;
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
            $connection = $this->healthCheckIndividual($connector_class, $connection);
        }
        return $connections;
    }

    public function healthCheckIndividual(Object $connector): array
    {
        $connector_class = $this->getConnectors($connector['connector']);
        if (empty($connector_class[$connector['connector']])) {
            return [];
        }
        $connector_class = $connector_class[$connector['connector']];
        $health = $connector_class->health($connector);
        return $connection = [
            'name' => $connector->name,
            'health' => $health['status'],
            'message' => $health['message'],
            'url' => '/localTools/viewConnection/' . $connector['id']
        ];
    }
}
