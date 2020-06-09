<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Error\Debugger;
use Cake\Utility\Inflector;

class CRUDComponent extends Component
{

    public function initialize(array $config): void
    {
        $this->Controller = $this->getController();
        $this->Table = $config['table'];
        $this->request = $config['request'];
        $this->TableAlias = $this->Table->getAlias();
        $this->ObjectAlias = \Cake\Utility\Inflector::singularize($this->TableAlias);
    }

    public function index(array $filters = [], array $quickFilterFields = [])
    {
        $params = $this->Controller->ParamHandler->harvestParams($filters);
        $query = $this->Table->find();
        $query = $this->setFilters($params, $query);
        $query = $this->setQuickFilters($params, $query, $quickFilterFields);
        if (!empty($conditions)) {
            $query->where([
                'OR' => $conditions
            ]);
        }
        if ($this->Controller->ParamHandler->isRest()) {
            $data = $query->all();
            $this->Controller->restResponsePayload = $this->Controller->RestResponse->viewData($data, 'json');
        } else {
            $this->Controller->loadComponent('Paginator');
            $data = $this->Controller->Paginator->paginate($query);
            $this->Controller->set('data', $data);
        }
    }

    public function add(): void
    {
        $data = $this->Table->newEmptyEntity();
        if ($this->request->is('post')) {
            $data = $this->Table->patchEntity($data, $this->request->getData());
            if ($this->Table->save($data)) {
                $message = __('%s added.', $this->ObjectAlias);
                if ($this->ParamHandler->isRest()) {
                    $data = $this->Table->get($id);
                    $this->Controller->restResponsePayload = $this->RestResponse->viewData($data, 'json');
                } else {
                    $this->Controller->Flash->success($message);
                    $this->Controller->redirect(['action' => 'index']);
                }
            } else {
                $message = __('%s could not be added.', $this->ObjectAlias);
                if ($this->Controller->_isRest()) {

                } else {
                    $this->Controller->Flash->error($message);
                }
            }
        }
    }

    public function edit($id): void
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid %s.', $this->ObjectAlias));
        }
        $data = $this->Table->get($id);
        if ($this->request->is(['post', 'put'])) {
            $this->Table->patchEntity($data, $this->request->getData());
            if ($this->Table->save($data)) {
                $message = __('%s updated.', $this->ObjectAlias);
                if ($this->ParamHandler->isRest()) {
                    $data = $this->Table->get($id);
                    $this->Controller->restResponsePayload = $this->RestResponse->viewData($data, 'json');
                } else {
                    $this->Controller->Flash->success($message);
                    $this->Controller->redirect(['action' => 'index']);
                }
            } else {
                if ($this->ParamHandler->isRest()) {

                }
            }
        }
        $this->Controller->set('data', $data);
    }

    public function view($id, $params): void
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid %s.', $this->ObjectAlias));
        }

        $data = $this->Table->get($id, $params);
        if ($this->Controller->ParamHandler->isRest()) {
            $this->Controller->restResponsePayload = $this->Controller->RestResponse->viewData($data, 'json');
        }
        $this->Controller->set('data', $data);
    }

    public function delete($id): void
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid %s.', $this->ObjectAlias));
        }
        $data = $this->Table->get($id);
        if ($this->request->is('post') || $this->request->is('delete')) {
            if ($this->Table->delete($data)) {
                $message = __('%s deleted.', $this->ObjectAlias);
                if ($this->Controller->ParamHandler->isRest()) {
                    $data = $this->Table->get($id);
                    $this->Controller->restResponsePayload = $this->RestResponse->saveSuccessResponse($this->TableAlias, 'delete', $id, 'json', $message);
                } else {
                    $this->Controller->Flash->success($message);
                    $this->redirect($this->referer());
                }
            }
        }
        $this->Controller->set('metaGroup', 'ContactDB');
        $this->Controller->set('scope', 'users');
        $this->Controller->set('id', $data['id']);
        $this->Controller->set('data', $data);
        $this->Controller->viewBuilder()->setLayout('ajax');
        $this->Controller->render('/genericTemplates/delete');
    }

    protected function massageFilters(array $params): array
    {
        $massagedFilters = [
            'simpleFilters' => [],
            'relatedFilters' => []
        ];
        if (!empty($params)) {
            foreach ($params as $param => $paramValue) {
                if (strpos($params, '.') !== null) {
                    $params = explode('.', $params);
                    if ($param[0] === $this->Table->alias) {
                        $massagedFilters['simpleFilters'][implode('.', $param)] = $paramValue;
                    } else {
                        $massagedFilters['relatedFilters'][implode('.', $param)] = $paramsValue;
                    }
                } else {
                    $massagedFilters['simpleFilters'][] = $params;
                }
            }
        }
        return $massagedFilters;
    }

    protected function setQuickFilters(array $params, \Cake\ORM\Query $query, array $quickFilterFields): \Cake\ORM\Query
    {
        $queryConditions = [];
        if (!empty($params['quickFilter']) && !empty($quickFilterFields)) {
            foreach ($quickFilterFields as $filterField) {
                $queryConditions[$filterField] = $params['quickFilter'];
            }
            $query->where(['OR' => $queryConditions]);
        }
        return $query;
    }

    protected function setFilters($params, \Cake\ORM\Query $query): \Cake\ORM\Query
    {
        $params = $this->massageFilters($params);
        $conditions = array();
        if (!empty($params['simpleFilters'])) {
            foreach ($params['simpleFilters'] as $filter => $filterValue) {
                if (strlen(trim($filterValue, '%')) === strlen($filterValue)) {
                    $query->where([$filter => $filterValue]);
                } else {
                    $query->like([$filter => $filterValue]);
                }
            }
        }
        if (!empty($params['relatedFilters'])) {
            foreach ($params['relatedFilters'] as $filter => $filterValue) {
                $filterParts = explode('.', $filter);
                $query->matching($filterParts[0], function(\Cake\ORM\Query $q){
                    if (strlen(trim($filterValue, '%')) === strlen($filterValue)) {
                        return $q->where([$filter => $filterValue]);
                    } else {
                        return $q->like([$filter => $filterValue]);
                    }
                });
            }
        }
        return $query;
    }
}
