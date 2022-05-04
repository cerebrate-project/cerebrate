<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Inflector;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use \Cake\Database\Expression\QueryExpression;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Core\Configure;

class InstanceController extends AppController
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->set('metaGroup', !empty($this->isAdmin) ? 'Cerebrate' : 'Administration');
    }

    public function home()
    {
        $statistics = $this->Instance->getStatistics();
        $this->set('statistics', $statistics);
    }

    public function status()
    {
        $data = file_get_contents(APP . 'VERSION.json');
        $data = json_decode($data, true);
        $data['user'] = $this->ACL->getUser();
        return $this->RestResponse->viewData($data, 'json');
    }

    public function searchAll()
    {
        $searchValue = $this->request->getQuery('search');
        $model = $this->request->getQuery('model', null);
        $limit = $this->request->getQuery('limit', 5);
        if (!empty($this->request->getQuery('show_all', false))) {
            $limit = null;
        }
        $data = [];
        if (!empty($searchValue)) {
            $data = $this->Instance->searchAll($searchValue, $this->ACL->getUser(), $limit, $model);
        }
        if ($this->ParamHandler->isRest()) {
            return $this->RestResponse->viewData($data, 'json');
        }
        $this->set('data', $data);
    }

    public function migrationIndex()
    {
        $migrationStatus = $this->Instance->getMigrationStatus();

        $this->loadModel('Phinxlog');
        $status = $this->Phinxlog->mergeMigrationLogIntoStatus($migrationStatus['status']);

        foreach ($status as $i => $entry) {
            if (!empty($entry['plugin'])) {
                $pluginTablename = sprintf('%s_phinxlog', Inflector::underscore($entry['plugin']));
                $pluginTablename = str_replace(['\\', '/', '.'], '_', $pluginTablename);
                $status[$i] = $this->Phinxlog->mergeMigrationLogIntoStatus([$entry], $pluginTablename)[0];

            }
        }
        usort($status, function($a, $b) {
            return strcmp($b['id'], $a['id']);
        });
        if ($this->ParamHandler->isRest()) {
            return $this->RestResponse->viewData([
                'status' => $status,
                'updateAvailables' => $migrationStatus['updateAvailables'],
            ], 'json');
        }
        $this->set('status', $status);
        $this->set('updateAvailables', $migrationStatus['updateAvailables']);
    }

    public function migrate($version=null) {
        if ($this->request->is('post')) {
            if (is_null($version)) {
                $migrateResult = $this->Instance->migrate();
            } else {
                $migrateResult = $this->Instance->migrate(['target' => $version]);
            }
            if ($this->ParamHandler->isRest() || $this->ParamHandler->isAjax()) {
                if ($migrateResult['success']) {
                    return $this->RestResponse->saveSuccessResponse('instance', 'migrate', false, false, __('Migration sucessful'));
                } else {
                    return $this->RestResponse->saveFailResponse('instance', 'migrate', false, $migrateResult['error']);
                }
            } else {
                if ($migrateResult['success']) {
                    $this->Flash->success(__('Migration sucessful'));
                    $this->redirect(['action' => 'migrationIndex']);
                } else {
                    $this->Flash->error(__('Migration fail'));
                    $this->redirect(['action' => 'migrationIndex']);
                }
            }
        }
        $migrationStatus = $this->Instance->getMigrationStatus();
        $this->set('title',  __n('Run database update?', 'Run all database updates?', count($migrationStatus['updateAvailables'])));
        $this->set('question', __('The process might take some time.'));
        $this->set('actionName', __n('Run update', 'Run all updates', count($migrationStatus['updateAvailables'])));
        $this->set('path', ['controller' => 'instance', 'action' => 'migrate']);
        $this->render('/genericTemplates/confirm');
    }

    public function rollback($version=null) {
        if ($this->request->is('post')) {
            if (is_null($version)) {
                $migrateResult = $this->Instance->rollback();
            } else {
                $migrateResult = $this->Instance->rollback(['target' => $version]);
            }
            if ($this->ParamHandler->isRest() || $this->ParamHandler->isAjax()) {
                if ($migrateResult['success']) {
                    return $this->RestResponse->saveSuccessResponse('instance', 'rollback', false, false, __('Rollback sucessful'));
                } else {
                    return $this->RestResponse->saveFailResponse('instance', 'rollback', false, $migrateResult['error']);
                }
            } else {
                if ($migrateResult['success']) {
                    $this->Flash->success(__('Rollback sucessful'));
                    $this->redirect(['action' => 'migrationIndex']);
                } else {
                    $this->Flash->error(__('Rollback fail'));
                    $this->redirect(['action' => 'migrationIndex']);
                }
            }
        }
        $migrationStatus = $this->Instance->getMigrationStatus();
        $this->set('title',  __('Run database rollback?'));
        $this->set('question', __('The process might take some time.'));
        $this->set('actionName', __('Run rollback'));
        $this->set('path', ['controller' => 'instance', 'action' => 'rollback']);
        $this->render('/genericTemplates/confirm');
    }

    public function settings()
    {
        $this->Settings = $this->getTableLocator()->get('Settings');
        $all = $this->Settings->getSettings(true);
        if ($this->ParamHandler->isRest()) {
            return $this->RestResponse->viewData([
                'settingsProvider' => $all['settingsProvider'],
                'settings' => $all['settings'],
                'settingsFlattened' => $all['settingsFlattened'],
                'notices' => $all['notices'],
            ], 'json');
        }
        $this->set('settingsProvider', $all['settingsProvider']);
        $this->set('settings', $all['settings']);
        $this->set('settingsFlattened', $all['settingsFlattened']);
        $this->set('notices', $all['notices']);
    }

    public function saveSetting()
    {
        if ($this->request->is('post')) {
            $data = $this->ParamHandler->harvestParams([
                'name',
                'value'
            ]);
            $this->Settings = $this->getTableLocator()->get('Settings');
            $errors = $this->Settings->saveSetting($data['name'], $data['value']);
            $message = __('Could not save setting `{0}`', $data['name']);
            if (empty($errors)) {
                $message = __('Setting `{0}` saved', $data['name']);
                $data = $this->Settings->getSetting($data['name']);
            }
            $this->CRUD->setResponseForController('saveSetting', empty($errors), $message, $data, $errors);
            $responsePayload = $this->CRUD->getResponsePayload();
            if (!empty($responsePayload)) {
                return $responsePayload;
            }
        }
    }
}
