<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Migrations\Migrations;

class InstanceTable extends AppTable
{
    protected $activePlugins = ['Tags'];
    
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator;
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
