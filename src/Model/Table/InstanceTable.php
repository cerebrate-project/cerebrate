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
    protected $activePlugins = ['Tags'];
    public $seachAllTables = ['Broods', 'Individuals', 'Organisations', 'SharingGroups', 'Users', 'EncryptionKeys', ];

    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator;
    }

    public function getStatistics($days=7): array
    {
        $models = ['Individuals', 'Organisations', 'Alignments', 'EncryptionKeys', 'SharingGroups', 'Users', 'Tags.Tags'];
        foreach ($models as $model) {
            $table = TableRegistry::getTableLocator()->get($model);
            $statistics[$model]['amount'] = $table->find()->all()->count();
            if ($table->behaviors()->has('Timestamp')) {
                $query = $table->find();
                $query->select([
                        'count' => $query->func()->count('id'),
                        'date' => 'DATE(modified)',
                    ])
                    ->where(['modified >' => new \DateTime("-{$days} days")])
                    ->group(['date'])
                    ->order(['date']);
                $data = $query->toArray();
                $interval = new \DateInterval('P1D');
                $period = new \DatePeriod(new \DateTime("-{$days} days"), $interval, new \DateTime());
                $timeline = [];
                foreach ($period as $date) {
                    $timeline[$date->format("Y-m-d")] = [
                        'time' => $date->format("Y-m-d"),
                        'count' => 0
                    ];
                }
                foreach ($data as $entry) {
                    $timeline[$entry->date]['count'] = $entry->count;
                }
                $statistics[$model]['timeline'] = array_values($timeline);
                
                $startCount = $table->find()->where(['modified <' => new \DateTime("-{$days} days")])->all()->count();
                $endCount = $statistics[$model]['amount'];
                $statistics[$model]['variation'] = $endCount - $startCount;
            } else {
                $statistics[$model]['timeline'] = [];
                $statistics[$model]['variation'] = 0;
            }
        }
        return $statistics;
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
        foreach ($this->activePlugins as $pluginName) {
            $pluginStatus = $migrations->status([
                'plugin' => $pluginName
            ]);
            $pluginStatus = array_map(function ($entry) use ($pluginName) {
                $entry['plugin'] = $pluginName;
                return $entry;
            }, $pluginStatus);
            $status = array_merge($status, $pluginStatus);
        }
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
