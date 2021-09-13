<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Migrations\Migrations;
use Cake\Http\Exception\MethodNotAllowedException;

class InstanceTable extends AppTable
{

    public $seachAllTables = ['Broods', 'Individuals', 'Organisations', 'SharingGroups', 'Users', 'EncryptionKeys', ];

    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator;
    }

    public function searchAll($value, $limit=5, $model=null)
    {
        $results = [];
        $models = $this->seachAllTables;
        if (!is_null($model)) {
            if (in_array($model, $this->seachAllTables)) {
                $models = [$model];
            } else {
                return $results; // Cannot search in this model
            }
        }
        foreach ($models as $tableName) {
            $controller = $this->getController($tableName);
            $table = TableRegistry::get($tableName);
            $query = $table->find();
            $quickFilterOptions = $this->getQuickFiltersFieldsFromController($controller);
            $containFields = $this->getContainFieldsFromController($controller);
            if (empty($quickFilterOptions)) {
                continue; // make sure we are filtering on something
            }
            $params = ['quickFilter' => $value];
            $query = $controller->CRUD->setQuickFilters($params, $query, $quickFilterOptions);
            if (!empty($containFields)) {
                $query->contain($containFields);
            }
            $results[$tableName]['amount'] = $query->count();
            $result = $query->limit($limit)->all()->toList();
            if (!empty($result)) {
                $results[$tableName]['entries'] = $result;
            }
        }
        return $results;
    }

    public function getController($name)
    {
        $controllerName = "\\App\\Controller\\{$name}Controller";
        if (!class_exists($controllerName)) {
            throw new MethodNotAllowedException(__('Model `{0}` does not exists', $model));
        }
        $controller = new $controllerName;
        return $controller;
    }

    public function getQuickFiltersFieldsFromController($controller)
    {
        return !empty($controller->quickFilterFields) ? $controller->quickFilterFields : [];
    }

    public function getContainFieldsFromController($controller)
    {
        return !empty($controller->containFields) ? $controller->containFields : [];
    }

    public function getMigrationStatus()
    {
        $migrations = new Migrations();
        $status = $migrations->status();
        $status = array_reverse($status);

        $updateAvailables = array_filter($status, function ($update) {
            return $update['status'] != 'up';
        });
        return [
            'status' => $status,
            'updateAvailables' => $updateAvailables,
        ];
    }

    public function migrate($version=null) {
        $migrations = new Migrations();
        if (is_null($version)) {
            $migrationResult = $migrations->migrate();
        } else {
            $migrationResult = $migrations->migrate(['target' => $version]);
        }
        return [
            'success' => true
        ];
    }

    public function rollback($version=null) {
        $migrations = new Migrations();
        if (is_null($version)) {
            $migrationResult = $migrations->rollback();
        } else {
            $migrationResult = $migrations->rollback(['target' => $version]);
        }
        return [
            'success' => true
        ];
    }
}
