<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Error\Debugger;
use Cake\Utility\Hash;
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

    public function index(array $options): void
    {
        if (!empty($options['quickFilters'])) {
            if (empty($options['filters'])) {
                $options['filters'] = [];
            }
            $options['filters'][] = 'quickFilter';
        }
        $params = $this->Controller->ParamHandler->harvestParams(empty($options['filters']) ? [] : $options['filters']);
        $query = $this->Table->find();
        $query = $this->setFilters($params, $query);
        $query = $this->setQuickFilters($params, $query, empty($options['quickFilters']) ? [] : $options['quickFilters']);
        if (!empty($options['contain'])) {
            $query->contain($options['contain']);
        }
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

    public function add(array $params = []): void
    {
        $data = $this->Table->newEmptyEntity();
        if ($this->request->is('post')) {
            $input = $this->request->getData();
            if (!empty($params['override'])) {
                foreach ($params['override'] as $field => $value) {
                    $input[$field] = $value;
                }
            }
            $data = $this->Table->patchEntity($data, $input);
            if ($this->Table->save($data)) {
                $message = __('{0} added.', $this->ObjectAlias);
                if ($this->Controller->ParamHandler->isRest()) {
                    $this->Controller->restResponsePayload = $this->RestResponse->viewData($data, 'json');
                } else {
                    $this->Controller->Flash->success($message);
                    if (!empty($params['displayOnSuccess'])) {
                        $this->Controller->set('entity', $data);
                        $this->Controller->set('referer', $this->Controller->referer());
                        $this->Controller->render($params['displayOnSuccess']);
                        return;
                    }
                    $this->Controller->redirect(['action' => 'index']);
                }
            } else {
                $message = __('{0} could not be added.', $this->ObjectAlias);
                if ($this->Controller->ParamHandler->isRest()) {

                } else {
                    $this->Controller->Flash->error($message);
                }
            }
        }
        $this->Controller->set('entity', $data);
    }

    public function edit(int $id, array $params = []): void
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid {0}.', $this->ObjectAlias));
        }
        $data = $this->Table->get($id, isset($params['get']) ? $params['get'] : []);
        if ($this->request->is(['post', 'put'])) {
            $input = $this->request->getData();
            if (!empty($params['override'])) {
                foreach ($params['override'] as $field => $value) {
                    $input[$field] = $value;
                }
            }
            $this->Table->patchEntity($data, $this->request->getData());
            if ($this->Table->save($data)) {
                $message = __('{0} updated.', $this->ObjectAlias);
                if ($this->Controller->ParamHandler->isRest()) {
                    $this->Controller->restResponsePayload = $this->RestResponse->viewData($data, 'json');
                } else {
                    $this->Controller->Flash->success($message);
                    $this->Controller->redirect(['action' => 'index']);
                }
            } else {
                if ($this->Controller->ParamHandler->isRest()) {

                }
            }
        }
        $this->Controller->set('entity', $data);
    }

    public function view(int $id, array $params = []): void
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid {0}.', $this->ObjectAlias));
        }

        $data = $this->Table->get($id, $params);
        if ($this->Controller->ParamHandler->isRest()) {
            $this->Controller->restResponsePayload = $this->Controller->RestResponse->viewData($data, 'json');
        }
        $this->Controller->set('entity', $data);
    }

    public function delete(int $id): void
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid {0}.', $this->ObjectAlias));
        }
        $data = $this->Table->get($id);
        if ($this->request->is('post') || $this->request->is('delete')) {
            if ($this->Table->delete($data)) {
                $message = __('{0} deleted.', $this->ObjectAlias);
                if ($this->Controller->ParamHandler->isRest()) {
                    $data = $this->Table->get($id);
                    $this->Controller->restResponsePayload = $this->RestResponse->saveSuccessResponse($this->TableAlias, 'delete', $id, 'json', $message);
                } else {
                    $this->Controller->Flash->success($message);
                    $this->Controller->redirect($this->Controller->referer());
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
                if (strpos($param, '.') !== false) {
                    $param = explode('.', $param);
                    if ($param[0] === $this->Table->getAlias()) {
                        $massagedFilters['simpleFilters'][implode('.', $param)] = $paramValue;
                    } else {
                        $massagedFilters['relatedFilters'][implode('.', $param)] = $paramValue;
                    }
                } else {
                    $massagedFilters['simpleFilters'][$param] = $paramValue;
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
                if ($filter === 'quickFilter') {
                    continue;
                }
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
                $query->matching($filterParts[0], function(\Cake\ORM\Query $q) use ($filterValue, $filter) {
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
