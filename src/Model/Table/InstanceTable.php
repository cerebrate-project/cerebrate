<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Migrations\Migrations;
use Cake\Filesystem\Folder;
use Cake\Http\Exception\MethodNotAllowedException;

class InstanceTable extends AppTable
{
    protected $activePlugins = ['Tags', 'ADmad/SocialAuth'];
    public $seachAllTables = [];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('AuditLog');
        $this->setDisplayField('name');
        $this->setSearchAllTables();
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator;
    }

    public function setSearchAllTables(): void
    {
        $this->seachAllTables = [
            'Broods' => ['conditions' => false, 'afterFind' => false],
            'Individuals' => ['conditions' => false, 'afterFind' => false],
            'Organisations' => ['conditions' => false, 'afterFind' => false],
            'SharingGroups' => [
                'conditions' => false,
                'afterFind' => function($result, $user) {
                    foreach ($result as $i => $row) {
                        if (empty($user['role']['perm_admin'])) {
                            $orgFound = false;
                            if (!empty($row['sharing_group_orgs'])) {
                                foreach ($row['sharing_group_orgs'] as $org) {
                                    if ($org['id'] === $user['organisation_id']) {
                                        $orgFound = true;
                                    }
                                }
                            }
                            if ($row['organisation_id'] !== $user['organisation_id'] && !$orgFound) {
                                unset($result[$i]);
                            }
                        }
                    }
                    return $result;
                },
            ],
            'Users' => [
                'conditions' => function($user) {
                    $conditions = [];
                    if (empty($user['role']['perm_admin'])) {
                        $conditions['Users.organisation_id'] = $user['organisation_id'];
                    }
                    return $conditions;
                },
                'afterFind' => function ($result, $user) {
                    return $result;
                },
            ],
            'EncryptionKeys' => ['conditions' => false, 'afterFind' => false],
        ];
    }

    public function getStatistics(int $days=30): array
    {
        $models = ['Individuals', 'Organisations', 'Alignments', 'EncryptionKeys', 'SharingGroups', 'Users', 'Broods', 'Tags.Tags'];
        foreach ($models as $model) {
            $table = TableRegistry::getTableLocator()->get($model);
            $statistics[$model] = $this->getActivityStatisticsForModel($table, $days);
        }
        return $statistics;
    }

    public function searchAll($value, $user, int $limit=5, $model=null)
    {
        $results = [];
        $models = $this->seachAllTables;
        if (!is_null($model)) {
            if (in_array($model, array_keys($this->seachAllTables))) {
                $models = [$model => $this->seachAllTables[$model]];
            } else {
                return $results; // Cannot search in this model
            }
        }

        // search in metafields. FIXME?: Use meta-fields type handler to search for meta-field values
        if (is_null($model)) {
            $metaFieldTable = TableRegistry::get('MetaFields');
            $query = $metaFieldTable->find()->where([
                'value LIKE' => '%' . $value . '%'
            ]);
            $results['MetaFields']['amount'] = $query->count();
            $result = $query->limit($limit)->all()->toList();
            if (!empty($result)) {
                $results['MetaFields']['entries'] = $result;
            }
        }

        foreach ($models as $tableName => $tableConfig) {
            $controller = $this->getController($tableName);
            $table = TableRegistry::get($tableName);
            $query = $table->find();
            $quickFilters = $this->getQuickFiltersFieldsFromController($controller);
            $containFields = $this->getContainFieldsFromController($controller);
            if (empty($quickFilters)) {
                continue; // make sure we are filtering on something
            }
            $params = ['quickFilter' => $value];
            $quickFilterOptions = ['quickFilters' => $quickFilters];
            $query = $controller->CRUD->setQuickFilters($params, $query, $quickFilterOptions);
            if (!empty($tableConfig['conditions'])) {
                $whereClause = [];
                if (is_callable($tableConfig['conditions'])) {
                    $whereClause = $tableConfig['conditions']($user);
                } else {
                    $whereClause = $tableConfig['conditions'];
                }
                $query->where($whereClause);
            }
            if (!empty($containFields)) {
                $query->contain($containFields);
            }
            if (!empty($tableConfig['contain'])) {
                $query->contain($tableConfig['contain']);
            }
            if (empty($tableConfig['afterFind'])) {
                $results[$tableName]['amount'] = $query->count();
            }
            $result = $query->limit($limit)->all()->toList();
            if (!empty($result)) {
                if (!empty($tableConfig['afterFind'])) {
                    $result = $tableConfig['afterFind']($result, $user);
                }
                $results[$tableName]['entries'] = $result;
                $results[$tableName]['amount'] = count($result);
            }
        }
        return $results;
    }

    public function getController($name)
    {
        $controllerName = "\\App\\Controller\\{$name}Controller";
        if (!class_exists($controllerName)) {
            throw new MethodNotAllowedException(__('Model `{0}` does not exists', $name));
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
        $command = ROOT . '/bin/cake schema_cache clear';
        $output = shell_exec($command);
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

    public function getAvailableThemes()
    {
        $themesPath = ROOT . '/webroot/css/themes';
        $dir = new Folder($themesPath);
        $filesRegex = 'bootstrap-(?P<themename>\w+)\.css';
        $themeRegex = '/' . 'bootstrap-(?P<themename>\w+)\.css' . '/';
        $files = $dir->find($filesRegex);
        $themes = [];
        foreach ($files as $filename) {
            $matches = [];
            $themeName = preg_match($themeRegex, $filename, $matches);
            if (!empty($matches['themename'])) {
                $themes[] =  $matches['themename'];
            }
        }
        return $themes;
    }

    public function getTopology($mermaid = true): mixed
    {
        $BroodsModel = TableRegistry::getTableLocator()->get('Broods');
        $LocalToolsModel = TableRegistry::getTableLocator()->get('LocalTools');
        $connectors = $LocalToolsModel->getConnectors();
        $connections = $LocalToolsModel->extractMeta($connectors, true);
        $broods = $BroodsModel->find()->select(['id', 'uuid', 'url', 'name', 'pull'])->disableHydration()->toArray();
        foreach ($broods as $k => $brood) {
            $broods[$k]['status'] = $BroodsModel->queryStatus($brood['id']);
        }
        $data = [
            'broods' => $broods,
            'tools' => $LocalToolsModel->extractMeta($connectors, true)
        ];
        if ($mermaid) {
            return $this->generateTopologyMermaid($data);
        }
        return $data;
    }

    public function generateTopologyMermaid($data)
    {
        $version = json_decode(file_get_contents(APP . 'VERSION.json'), true)["version"];
        $newest = $version;
        $broods = '';
        $edges = '';
        // pre-run the loop to get the latest version
        foreach ($data['broods'] as $brood) {
            if ($brood['status']['code'] === 200) {
                if (version_compare($brood['status']['response']['version'], $newest) > 0) {
                    $newest = $brood['status']['response']['version'];
                }
            }
        }
        foreach ($data['broods'] as $brood) {
            $status = '';
            if ($brood['status']['code'] === 200) {
                $status = sprintf(
                    '<br />Ping: %sms<br />Version: <span class="%s">v%s</span><br />Role: %s<br />',
                    h($brood['status']['ping']),
                    $brood['status']['response']['version'] === $newest ? 'text-success' : 'text-danger',
                    h($brood['status']['response']['version']) . ($brood['status']['response']['version'] !== $newest ? ' - outdated' : ''),
                    h($brood['status']['response']['role']['name'])
                );
            }
            $broods .= sprintf(
                "%s%s    end" . PHP_EOL,
                sprintf(
                    '    subgraph brood_%s[fas:fa-network-wired Brood #%s]' . PHP_EOL,
                    h($brood['id']),
                    h($brood['id'])    
                ),
                sprintf(
                    "        cerebrate_%s[%s<br />%s<a href='/broods/view/%s'>fas:fa-eye</a>]" . PHP_EOL,
                    h($brood['id']),
                    '<span class="font-weight-bold">' . h($brood['name']) . '</span>',
                    sprintf(
                        "Connected: <span class='%s' title='%s'>%s</span>%s",
                        $brood['status']['code'] === 200 ? 'text-success' : 'text-danger',
                        h($brood['status']['code']),
                        $brood['status']['code'] === 200 ? 'fas:fa-check' : 'fas:fa-times',
                        $status
                    ),
                    h($brood['id']),
                )
                
            );
            $edges .= sprintf(
                '    C1%s---cerebrate_%s' . PHP_EOL,
                $brood['pull'] ? '<' : '',
                h($brood['id'])
            );
        }
        $tools = '';
        foreach ($data['tools'] as $tool) {
            $tools .= sprintf(
                '            subgraph instance_local_tools_%s[%s %s connector]' . PHP_EOL . '                direction TB' . PHP_EOL,
                h($tool['name']),
                isset($tool['logo']) ? '<img src="/img/local_tools/' . h($tool['logo']) . '" style="width: 50px; height:50px;" />' : 'fas:fa-wrench',
                h($tool['name'])
            );
            foreach ($tool['connections'] as $connection) {
                $tools .= sprintf(
                    "                %s[%s<br />%s<br />%s]" . PHP_EOL,
                    h($connection['name']),
                    h($connection['name']),
                    sprintf(
                        __('Health') . ': <span title="%s" class="%s">%s</span>',
                        h($connection['message']),
                        $connection['health'] === 1 ? 'text-success' : 'text-danger',
                        $connection['health'] === 1 ? 'fas:fa-check' : 'fas:fa-times'
                    ),
                    sprintf(
                        "<a href='%s'>fas:fa-eye</a>",
                        h($connection['url'])
                    )
                );
            }
            $tools .= '            end' . PHP_EOL;
        }
        $this_cerebrate = sprintf(
            'C1[My Cerebrate<br />Version: <span class="%s">v%s</span>]',
            $version === $newest ? 'text-success' : 'text-danger',
            $version
        );
        $md = sprintf(
'flowchart TB
    subgraph instance[fas:fa-network-wired My Brood]
        direction TB
        %s
        subgraph instance_local_tools[fa:fa-tools Local Tools]
            direction LR
%s
        end
    end
%s%s',    
            $this_cerebrate,
            $tools,
            $broods,
            $edges
        );
        return $md;
    }
}
