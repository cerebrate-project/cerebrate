<?php

namespace CommonConnectorTools;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Log\Log;
use Cake\Log\Engine\FileLog;
use Cake\Utility\Hash;


class CommonConnectorTools
{
    public $description = '';
    public $name = '';
    public $connectorName = '';
    public $exposedFunctions = [
        'diagnostics'
    ];
    public $version = '???';

    const STATE_INITIAL = 'Request issued';
    const STATE_ACCEPT = 'Request accepted';
    const STATE_CONNECTED = 'Connected';
    const STATE_SENDING_ERROR = 'Error while sending request';
    const STATE_CANCELLED = 'Request cancelled';
    const STATE_DECLINED = 'Request declined by remote';

    public function __construct()
    {
        if (empty(Log::getConfig("LocalToolDebug{$this->connectorName}"))) {
            Log::setConfig("LocalToolDebug{$this->connectorName}", [
                'className' => FileLog::class,
                'path' => LOGS,
                'file' => "{$this->connectorName}-debug",
                'scopes' => [$this->connectorName],
                'levels' => ['notice', 'info', 'debug'],
            ]);
        }
        if (empty(Log::getConfig("LocalToolError{$this->connectorName}"))) {
            Log::setConfig("LocalToolError{$this->connectorName}", [
                'className' => FileLog::class,
                'path' => LOGS,
                'file' => "{$this->connectorName}-error",
                'scopes' => [$this->connectorName],
                'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
            ]);
        }
    }

    protected function logDebug($message)
    {
        Log::debug($message, [$this->connectorName]);
    }

    protected function logError($message, $scope=[])
    {
        Log::error($message, [$this->connectorName]);
    }

    public function addExposedFunction(string $functionName): void
    {
        $this->exposedFunctions[] = $functionName;
    }

    public function getBatchActionFunctions(): array
    {
        return array_filter($this->exposedFunctions, function($function) {
            return $function['type'] == 'batchAction';
        });
    }

    public function runAction($action, $params) {
        if (!in_array($action, $exposedFunctions)) {
            throw new MethodNotAllowedException(__('Invalid connector function called.'));
        }
        return $this->{$action}($params);
    }

    public function health(Object $connection): array
    {
        return 0;
    }

    public function captureOrganisation($input): bool
    {
        if (empty($input['uuid'])) {
            return false;
        }
        $organisations = \Cake\ORM\TableRegistry::getTableLocator()->get('Organisations');
        $organisations->captureOrg($input);
        return true;
    }

    public function getOrganisation(string $uuid): ?array
    {
        $organisations = \Cake\ORM\TableRegistry::getTableLocator()->get('Organisations');
        $org = $organisations->find()->where(['Organisations.uuid' => $uuid])->disableHydration()->first();
        return $org;
    }

    public function getOrganisations(): array
    {
        $organisations = \Cake\ORM\TableRegistry::getTableLocator()->get('Organisations');
        $orgs = $organisations->find()->disableHydration()->toArray();
        return $orgs;
    }

    public function getSharingGroups(): array
    {
        $sgs = \Cake\ORM\TableRegistry::getTableLocator()->get('SharingGroups');
        $sgs = $sgs->find()
            ->contain(['Organisations' => ['fields' => ['uuid']], 'SharingGroupOrgs' => ['fields' => ['uuid']]])
            ->disableHydration()
            ->toArray();
        return $sgs;
    }

    public function getFilteredOrganisations($filters, $returnObjects = false): array
    {
        $organisations = \Cake\ORM\TableRegistry::getTableLocator()->get('Organisations');
        $orgs = $organisations->find();
        $filterFields = ['type', 'nationality', 'sector'];
        foreach ($filterFields as $filterField) {
            if (!empty($filters[$filterField]) && $filters[$filterField] !== 'ALL') {
                $orgs = $orgs->where([$filterField => $filters[$filterField]]);
            }
        }
        if (!empty($filters['local']) && $filters['local'] !== '0') {
            $users = \Cake\ORM\TableRegistry::getTableLocator()->get('users');
            $org_ids = array_values(array_unique($users->find('list', [
                'valueField' => 'organisation_id'
            ])->toArray()));
            $orgs = $orgs->where(['id IN' => $org_ids]);
        }
        if ($returnObjects) {
            $orgs = $orgs->toArray();
        } else {
            $orgs = $orgs->disableHydration()->all();
        }
        return $orgs;
    }

    public function getFilteredSharingGroups($filters, $returnObjects = false): array
    {
        $SG = \Cake\ORM\TableRegistry::getTableLocator()->get('SharingGroups');
        $sgs = $SG->find();
        $filterFields = ['name', 'releasability'];
        $sgs->contain(['SharingGroupOrgs', 'Organisations']);
        foreach ($filterFields as $filterField) {
            if (!empty($filters[$filterField]) && $filters[$filterField] !== 'ALL') {
                if (is_string($filters[$filterField]) && strpos($filters[$filterField], '%') !== false) {
                    $sgs = $sgs->where(['SharingGroups.' . $filterField . ' LIKE' => $filters[$filterField]]);
                } else {
                    
                    $sgs = $sgs->where(['SharingGroups.' . $filterField => $filters[$filterField]]);
                }
            }
        }
        if ($returnObjects) {
            $sgs = $sgs->toArray();
        } else {
            $sgs = $sgs->disableHydration()->all();
        }
        return $sgs;
    }

    public function getOrganisationSelectorValues(): array
    {
        $results = [];
        $orgTable = \Cake\ORM\TableRegistry::getTableLocator()->get('Organisations');
        $fields = [
            'nationality' => 'nat',
            'sector' => 'sect',
            'type' => 'typ'
        ];
        foreach ($fields as $field => $temp_field) {
            $temp = Hash::extract(
                $orgTable->find()
                    ->select([$temp_field => 'DISTINCT (' . $field . ')'])
                    ->order([$temp_field => 'DESC'])
                    ->disableHydration()->toArray(),
                '{n}.' . $temp_field
            );
            foreach ($temp as $k => $v) {
                if (empty($v)) {
                    unset($temp[$k]);
                }
            }
            asort($temp, SORT_FLAG_CASE | SORT_NATURAL);
            $temp = array_merge(['ALL' => 'ALL'], $temp);
            $results[$field] = array_combine($temp, $temp);    
        }
        return $results;
    }

    public function captureSharingGroup($input, $user_id): bool
    {
        if (empty($input['uuid'])) {
            return false;
        }
        $sharing_groups = \Cake\ORM\TableRegistry::getTableLocator()->get('SharingGroups');
        $sharing_groups->captureSharingGroup($input, $user_id);
        return true;
    }

    public function remoteToolConnectionStatus(array $params, string $status): void
    {
        $remoteToolConnections = \Cake\ORM\TableRegistry::getTableLocator()->get('RemoteToolConnections');
        $remoteToolConnection = $remoteToolConnections->find()->where(
            [
                'local_tool_id' => $params['connection']['id'],
                'remote_tool_id' => $params['remote_tool']['id'],
                'brood_id' => $params['remote_cerebrate']['id']
            ]
        )->first();
        if (empty($remoteToolConnection)) {
            $data = $remoteToolConnections->newEmptyEntity();
            $entry = [
                'local_tool_id' => $params['connection']['id'],
                'remote_tool_id' => $params['remote_tool']['id'],
                'remote_tool_name' => $params['remote_tool']['name'],
                'brood_id' => $params['remote_cerebrate']['id'],
                'name' => '',
                'settings' => '',
                'status' => $status,
                'created' => time(),
                'modified' => time()
            ];
            $data = $remoteToolConnections->patchEntity($data, $entry);
            $remoteToolConnections->save($data);
        } else {
            $data = $remoteToolConnections->patchEntity($remoteToolConnection, ['status' => $status, 'modified' => time()]);
            $remoteToolConnections->save($data);
        }
    }

    public function initiateConnectionWrapper(array $params): array
    {
        $result = $this->initiateConnection($params);
        $this->remoteToolConnectionStatus($params, self::STATE_INITIAL);
        return $result;
    }

    public function acceptConnectionWrapper(array $params): array
    {
        $result = $this->acceptConnection($params);
        $this->remoteToolConnectionStatus($params, self::STATE_ACCEPT);
        return $result;
    }

    public function finaliseConnectionWrapper(array $params): bool
    {
        $result = $this->finaliseConnection($params);
        $this->remoteToolConnectionStatus($params, self::STATE_CONNECTED);
        return false;
    }

    public function diagnostics(array $params): array
    {
        return [];
    }
}

?>
