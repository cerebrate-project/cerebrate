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
        $this->ObjectAlias = Inflector::singularize($this->TableAlias);
        $this->MetaFields = $config['MetaFields'];
        $this->MetaTemplates = $config['MetaTemplates'];
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
        if ($this->Controller->ParamHandler->isRest()) {
            $data = $query->all();
            $this->Controller->restResponsePayload = $this->Controller->RestResponse->viewData($data, 'json');
        } else {
            $this->Controller->loadComponent('Paginator');
            $data = $this->Controller->Paginator->paginate($query);
            if (!empty($options['contextFilters'])) {
                $this->setFilteringContext($options['contextFilters'], $params);
            }
            $this->Controller->set('data', $data);
        }
    }
    
    /**
     * getResponsePayload Returns the adaquate response payload based on the request context
     *
     * @return false or Array
     */
    public function getResponsePayload()
    {
        if ($this->Controller->ParamHandler->isRest()) {
            return $this->Controller->restResponsePayload;
        } else if ($this->Controller->ParamHandler->isAjax() && $this->request->is(['post', 'put'])) {
            return $this->Controller->ajaxResponsePayload;
        }
        return false;
    }

    private function getMetaTemplates()
    {
        $metaTemplates = [];
        if (!empty($this->Table->metaFields)) {
            $metaQuery = $this->MetaTemplates->find();
            $metaQuery
                ->order(['is_default' => 'DESC'])
                ->where([
                    'scope' => $this->Table->metaFields,
                    'enabled' => 1
                ]);
            $metaQuery->contain(['MetaTemplateFields']);
            $metaTemplates = $metaQuery->all();
        }
        $this->Controller->set('metaTemplates', $metaTemplates);
        return true;
    }

    public function add(array $params = []): void
    {
        $this->getMetaTemplates();
        $data = $this->Table->newEmptyEntity();
        if (!empty($params['fields'])) {
            $this->Controller->set('fields', $params['fields']);
        }
        if ($this->request->is('post')) {
            $patchEntityParams = [
                'associated' => []
            ];
            if (!empty($params['id'])) {
                unset($params['id']);
            }
            $input = $this->__massageInput($params);
            if (!empty($params['fields'])) {
                $patchEntityParams['fields'] = $params['fields'];
            }
            $data = $this->Table->patchEntity($data, $input, $patchEntityParams);
            $savedData = $this->Table->save($data);
            if ($savedData !== false) {
                $message = __('{0} added.', $this->ObjectAlias);
                if (!empty($input['metaFields'])) {
                    $this->saveMetaFields($data->id, $input);
                }
                if ($this->Controller->ParamHandler->isRest()) {
                    $this->Controller->restResponsePayload = $this->RestResponse->viewData($data, 'json');
                } else if ($this->Controller->ParamHandler->isAjax()) {
                    $this->Controller->ajaxResponsePayload = $this->Controller->RestResponse->ajaxSuccessResponse($this->ObjectAlias, 'add', $savedData, $message);
                } else {
                    $this->Controller->Flash->success($message);
                    if (!empty($params['displayOnSuccess'])) {
                        $this->Controller->set('entity', $data);
                        $this->Controller->set('referer', $this->Controller->referer());
                        $this->Controller->render($params['displayOnSuccess']);
                        return;
                    }
                    if (empty($params['redirect'])) {
                        $this->Controller->redirect(['action' => 'view', $data->id]);
                    } else {
                        $this->Controller->redirect($params['redirect']);
                    }
                }
            } else {
                $this->Controller->isFailResponse = true;
                $validationMessage = $this->prepareValidationError($data);
                $message = __(
                    '{0} could not be added.{1}',
                    $this->ObjectAlias,
                    empty($validationMessage) ? '' : ' ' . __('Reason:{0}', $validationMessage)
                );
                if ($this->Controller->ParamHandler->isRest()) {
                } else if ($this->Controller->ParamHandler->isAjax()) {
                    $this->Controller->ajaxResponsePayload = $this->Controller->RestResponse->ajaxFailResponse($this->ObjectAlias, 'add', $data, $message, $validationMessage);
                } else {
                    $this->Controller->Flash->error($message);
                }
            }
        }
        $this->Controller->set('entity', $data);
    }

    private function prepareValidationError($data)
    {
        $validationMessage = '';
        if (!empty($data->getErrors())) {
            foreach ($data->getErrors() as $field => $errorData) {
                $errorMessages = [];
                foreach ($errorData as $key => $value) {
                    $errorMessages[] = $value;
                }
                $validationMessage .= __(' {1}', $field, implode(',', $errorMessages));
            }
        }
        return $validationMessage;
    }

    private function saveMetaFields($id, $input)
    {
        $this->Table->saveMetaFields($id, $input);
    }

    private function __massageInput($params)
    {
        $input = $this->request->getData();
        if (!empty($params['override'])) {
            foreach ($params['override'] as $field => $value) {
                $input[$field] = $value;
            }
        }
        if (!empty($params['removeEmpty'])) {
            foreach ($params['removeEmpty'] as $removeEmptyField) {
                if (empty($input[$removeEmptyField])) {
                    unset($input[$removeEmptyField]);
                }
            }
        }
        return $input;
    }

    public function edit(int $id, array $params = []): void
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid {0}.', $this->ObjectAlias));
        }
        $this->getMetaTemplates();
        $data = $this->Table->get($id, isset($params['get']) ? $params['get'] : []);
        $data = $this->getMetaFields($id, $data);
        if (!empty($params['fields'])) {
            $this->Controller->set('fields', $params['fields']);
        }
        if ($this->request->is(['post', 'put'])) {
            $patchEntityParams = [
                'associated' => []
            ];
            $input = $this->__massageInput($params);
            if (!empty($params['fields'])) {
                $patchEntityParams['fields'] = $params['fields'];
            }
            $data = $this->Table->patchEntity($data, $input, $patchEntityParams);
            $savedData = $this->Table->save($data);
            if ($savedData !== false) {
                $message = __('{0} `{1}` updated.', $this->ObjectAlias, $savedData->{$this->Table->getDisplayField()});
                if (!empty($input['metaFields'])) {
                    $this->MetaFields->deleteAll(['scope' => $this->Table->metaFields, 'parent_id' => $data->id]);
                    $this->saveMetaFields($data->id, $input);
                }
                if ($this->Controller->ParamHandler->isRest()) {
                    $this->Controller->restResponsePayload = $this->RestResponse->viewData($data, 'json');
                } else if ($this->Controller->ParamHandler->isAjax()) {
                    $this->Controller->ajaxResponsePayload = $this->Controller->RestResponse->ajaxSuccessResponse($this->ObjectAlias, 'edit', $savedData, $message);
                } else {
                    $this->Controller->Flash->success($message);
                    if (empty($params['redirect'])) {
                        $this->Controller->redirect(['action' => 'view', $id]);
                    } else {
                        $this->Controller->redirect($params['redirect']);
                    }
                }
            } else {
                $validationMessage = $this->prepareValidationError($data);
                $message = __(
                    __('{0} could not be modified.'),
                    $this->ObjectAlias,
                );
                if ($this->Controller->ParamHandler->isRest()) {
                } else if ($this->Controller->ParamHandler->isAjax()) {
                    $this->Controller->ajaxResponsePayload = $this->Controller->RestResponse->ajaxFailResponse($this->ObjectAlias, 'edit', $data, $message, $data->getErrors());
                } else {
                    $this->Controller->Flash->error($message);
                }
            }
        }
        $this->Controller->set('entity', $data);
    }

    public function attachMetaData($id, $data)
    {
        if (empty($this->Table->metaFields)) {
            return $data;
        }
        $query = $this->MetaFields->MetaTemplates->find();
        $metaFields = $this->Table->metaFields;
        $query->contain('MetaTemplateFields', function ($q) use ($id, $metaFields) {
            return $q->innerJoinWith('MetaFields')
                ->where(['MetaFields.scope' => $metaFields, 'MetaFields.parent_id' => $id]);
        });
        $query->innerJoinWith('MetaTemplateFields', function ($q) {
            return $q->contain('MetaFields')->innerJoinWith('MetaFields');
        });
        $query->group(['MetaTemplates.id'])->order(['MetaTemplates.is_default' => 'DESC']);
        $metaTemplates = $query->all();
        $data['metaTemplates'] = $metaTemplates;
        return $data;
    }

    public function getMetaFields($id, $data)
    {
        if (empty($this->Table->metaFields)) {
            return $data;
        }
        $query = $this->MetaFields->find();
        $query->where(['MetaFields.scope' => $this->Table->metaFields, 'MetaFields.parent_id' => $id]);
        $metaFields = $query->all();
        $data['metaFields'] = [];
        foreach($metaFields as $metaField) {
            $data['metaFields'][$metaField->meta_template_id][$metaField->field] = $metaField->value;
        }
        return $data;
    }

    public function view(int $id, array $params = []): void
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid {0}.', $this->ObjectAlias));
        }

        $data = $this->Table->get($id, $params);
        $data = $this->attachMetaData($id, $data);
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
                    $this->Controller->restResponsePayload = $this->RestResponse->viewData($data, 'json');
                } else if ($this->Controller->ParamHandler->isAjax()) {
                    $this->Controller->ajaxResponsePayload = $this->Controller->RestResponse->ajaxSuccessResponse($this->ObjectAlias, 'delete', $data, $message);
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
                if (is_array($filterValue)) {
                    $query->where([($filter . ' IN') => $filterValue]);
                } else {
                    if (strlen(trim($filterValue, '%')) === strlen($filterValue)) {
                        $query->where([$filter => $filterValue]);
                    } else {
                        $query->like([$filter => $filterValue]);
                    }
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

    protected function setFilteringContext($contextFilters, $params)
    {
        $filteringContexts = [];
        if (!isset($contextFilters['allow_all']) || $contextFilters['allow_all']) {
            $filteringContexts[] = ['label' => __('All')];
        }
        if (!empty($contextFilters['fields'])) {
            foreach ($contextFilters['fields'] as $field) {
                $contextsFromField = $this->getFilteringContextFromField($field);
                foreach ($contextsFromField as $contextFromField) {
                    $filteringContexts[] = [
                        'label' => Inflector::humanize($contextFromField),
                        'filterCondition' => [
                            $field => $contextFromField
                        ]
                    ];
                }
            }
        }
        if (!empty($contextFilters['custom'])) {
            $filteringContexts = array_merge($filteringContexts, $contextFilters['custom']);
        }
        $this->Controller->set('filteringContexts', $filteringContexts);
    }

    public function toggle(int $id, string $fieldName = 'enabled', array $params = []): void
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid {0}.', $this->ObjectAlias));
        }

        $data = $this->Table->get($id, $params);
        if ($this->request->is(['post', 'put'])) {
            if (isset($params['force_state'])) {
                $data->{$fieldName} = $params['force_state'];
            } else {
                $data->{$fieldName} = !$data->{$fieldName};
            }
            $savedData = $this->Table->save($data);
            if ($savedData !== false) {
                $message = __('{0} field {1}. (ID: {2} {3})',
                    $fieldName,
                    $data->{$fieldName} ? __('enabled') : __('disabled'),
                    Inflector::humanize($this->ObjectAlias),
                    $data->id
                );
                if ($this->Controller->ParamHandler->isRest()) {
                    $this->Controller->restResponsePayload = $this->RestResponse->viewData($data, 'json');
                } else if ($this->Controller->ParamHandler->isAjax()) {
                    $this->Controller->ajaxResponsePayload = $this->Controller->RestResponse->ajaxSuccessResponse($this->ObjectAlias, 'toggle', $savedData, $message);
                } else {
                    $this->Controller->Flash->success($message);
                    if (empty($params['redirect'])) {
                        $this->Controller->redirect(['action' => 'view', $id]);
                    } else {
                        $this->Controller->redirect($params['redirect']);
                    }
                }
            } else {
                $validationMessage = $this->prepareValidationError($data);
                $message = __(
                    '{0} could not be modified.{1}',
                    $this->ObjectAlias,
                    empty($validationMessage) ? '' : ' ' . __('Reason:{0}', $validationMessage)
                );
                if ($this->Controller->ParamHandler->isRest()) {
                } else if ($this->Controller->ParamHandler->isAjax()) {
                    $this->Controller->ajaxResponsePayload = $this->Controller->RestResponse->ajaxFailResponse($this->ObjectAlias, 'toggle', $message, $validationMessage);
                } else {
                    $this->Controller->Flash->error($message);
                    if (empty($params['redirect'])) {
                        $this->Controller->redirect(['action' => 'view', $id]);
                    } else {
                        $this->Controller->redirect($params['redirect']);
                    }
                }
            }
        }
        $this->Controller->set('entity', $data);
        $this->Controller->set('fieldName', $fieldName);
        $this->Controller->viewBuilder()->setLayout('ajax');
        $this->Controller->render('/genericTemplates/toggle');
    }

    public function toggleEnabled(int $id, array $path, string $fieldName = 'enabled'): bool
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid {0}.', $this->ObjectAlias));
        }
        $data = $this->Table->get($id);
        if ($this->request->is('post')) {
            $data[$fieldName] = $data[$fieldName] ? true : false;
            $this->Table->save($data);
            $this->Controller->restResponsePayload = $this->Controller->RestResponse->viewData(['value' => $data[$fieldName]], 'json');
        } else {
            if ($this->Controller->ParamHandler->isRest()) {
                $this->Controller->restResponsePayload = $this->Controller->RestResponse->viewData(['value' => $data[$fieldName]], 'json');
            } else {
                $this->Controller->set('fieldName', $fieldName);
                $this->Controller->set('currentValue', $data[$fieldName]);
                $this->Controller->set('path', $path);
                $this->Controller->render('/genericTemplates/ajaxForm');
            }
        }
    }

    private function getFilteringContextFromField($field)
    {
        $exploded = explode('.', $field);
        if (count($exploded) > 1) {
            $model = $exploded[0];
            $subField = $exploded[1];
            return $this->Table->{$model}->find()
                ->distinct([$subField])
                ->extract($subField)->toList();
        } else {
            return $this->Table->find()->distinct([$field])->all()->extract($field)->toList();
        }
    }
}
