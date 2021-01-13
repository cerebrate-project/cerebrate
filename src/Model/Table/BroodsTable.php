<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Http\Client;
use Cake\ORM\TableRegistry;
use Cake\Error\Debugger;

class BroodsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->BelongsTo(
            'Organisations'
        );
        $this->setDisplayField('name');
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator;
    }

    public function queryStatus($id)
    {
        $brood = $this->find()->where(['id' => $id])->first();
        $http = new Client();
        $start = microtime(true);
        $response = $http->get($brood['url'] . '/instance/status.json', [], [
            'headers' => [
                'Authorization' => $brood['authkey'],
                'Accept' => 'Application/json',
                'Content-type' => 'Application/json'
            ]
        ]);
        $ping = ((int)(100 * (microtime(true) - $start)));
        $errors = [
            403 => [
                'error' => __('Authentication failure'),
                'reason' => __('Invalid user credentials.')
            ],
            405 => [
                'error' => __('Insufficient privileges'),
                'reason' => __('The remote user account doesn\'t have the required privileges to synchronise.')
            ],
            500 => [
                'error' => __('Internal error'),
                'reason' => __('Something is probably broken on the remote side. Get in touch with the instance owner.')
            ]
        ];
        $result = [
            'code' => $response->getStatusCode()
        ];
        if ($response->isOk()) {
            $raw = $response->getJson();
            $result['response']['role'] = $raw['user']['role'];
            $result['response']['user'] = $raw['user']['username'];
            $result['response']['application'] = $raw['application'];
            $result['response']['version'] = $raw['version'];
            $result['ping'] = $ping;
        } else {
            $result['error'] = $errors[$result['code']]['error'];
            $result['reason'] = $errors[$result['code']]['reason'];
            $result['ping'] = $ping;
        }
        return $result;
    }

    public function queryIndex($id, $scope, $filter)
    {
        $brood = $this->find()->where(['id' => $id])->first();
        if (empty($brood)) {
            throw new NotFoundException(__('Brood not found'));
        }
        $http = new Client();
        $filterQuery = empty($filter) ? '' : '?quickFilter=' . urlencode($filter);
        $response = $http->get($brood['url'] . '/' . $scope . '/index.json' . $filterQuery , [], [
            'headers' => [
                'Authorization' => $brood['authkey'],
                'Accept' => 'Application/json',
                'Content-type' => 'Application/json'
            ]
        ]);
        if ($response->isOk()) {
            return $response->getJson();
        } else {
            return false;
        }
    }

    public function downloadOrg($brood_id, $org_id)
    {
        $query = $this->find();
        $brood = $query->where(['id' => $brood_id])->first();
        if (empty($brood)) {
            throw new NotFoundException(__('Brood not found'));
        }
        $http = new Client();
        $response = $http->get($brood['url'] . '/organisations/view/' . $org_id . '/index.json' , [], [
            'headers' => [
                'Authorization' => $brood['authkey'],
                'Accept' => 'Application/json',
                'Content-type' => 'Application/json'
            ]
        ]);
        if ($response->isOk()) {
            $org = $response->getJson();
            $this->Organisation = TableRegistry::getTableLocator()->get('Organisations');
            $result = $this->Organisation->captureOrg($org);
            return $result;
        } else {
            return false;
        }
    }
}
