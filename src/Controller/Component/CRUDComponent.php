<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Error\Debugger;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\View\ViewBuilder;

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
                    $this->Controller->restResponsePayload = $this->RestResponse->viewData($savedData, 'json');
                } else if ($this->Controller->ParamHandler->isAjax()) {
                    if (!empty($params['displayOnSuccess'])) {
                        $displayOnSuccess = $this->renderViewInVariable($params['displayOnSuccess'], ['entity' => $data]);
                        $this->Controller->ajaxResponsePayload = $this->Controller->RestResponse->ajaxSuccessResponse($this->ObjectAlias, 'add', $savedData, $message, ['displayOnSuccess' => $displayOnSuccess]);
                    } else {
                        $this->Controller->ajaxResponsePayload = $this->Controller->RestResponse->ajaxSuccessResponse($this->ObjectAlias, 'add', $savedData, $message);
                    }
                } else {
                    $this->Controller->Flash->success($message);
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
        $this->Table->saveMetaFields($id, $input, $this->Table);
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
                    $this->MetaFields->deleteAll(['scope' => $this->Table->metaFields, 'parent_id' => $savedData->id]);
                    $this->saveMetaFields($savedData->id, $input);
                }
                if ($this->Controller->ParamHandler->isRest()) {
                    $this->Controller->restResponsePayload = $this->RestResponse->viewData($savedData, 'json');
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
                    $this->ObjectAlias
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
        $query->group(['MetaTemplates.id', 'MetaTemplates.scope', 'MetaTemplates.name', 'MetaTemplates.namespace', 'MetaTemplates.description', 'MetaTemplates.version', 'MetaTemplates.uuid', 'MetaTemplates.source', 'MetaTemplates.enabled', 'MetaTemplates.is_default'])
            ->order(['MetaTemplates.is_default' => 'DESC']);
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
        $this->Controller->set('quickFilter', empty($quickFilterFields) ? [] : $quickFilterFields);
        if (!empty($params['quickFilter']) && !empty($quickFilterFields)) {
            $this->Controller->set('quickFilterValue', $params['quickFilter']);
            foreach ($quickFilterFields as $filterField) {
                $likeCondition = false;
                if (is_array($filterField)) {
                    $likeCondition = reset($filterField);
                    $filterFieldName = array_key_first($filterField);
                    $queryConditions[$filterFieldName . ' LIKE'] = '%' . $params['quickFilter'] .'%';
                } else {
                    $queryConditions[$filterField] = $params['quickFilter'];
                }
            }
            $query->where(['OR' => $queryConditions]);
        } else {
            $this->Controller->set('quickFilterValue', '');
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
                    $query = $this->setValueCondition($query, $filter, $filterValue);
                }
            }
        }
        if (!empty($params['relatedFilters'])) {
            foreach ($params['relatedFilters'] as $filter => $filterValue) {
                $filterParts = explode('.', $filter);
                $query = $this->setNestedRelatedCondition($query, $filterParts, $filterValue);
            }
        }
        return $query;
    }

    protected function setNestedRelatedCondition($query, $filterParts, $filterValue)
    {
        $modelName = $filterParts[0];
        if (count($filterParts) == 2) {
            $fieldName = implode('.', $filterParts);
            $query = $this->setRelatedCondition($query, $modelName, $fieldName, $filterValue);
        } else {
            $filterParts = array_slice($filterParts, 1);
            $query = $query->matching($modelName, function(\Cake\ORM\Query $q) use ($filterParts, $filterValue) {
                return $this->setNestedRelatedCondition($q, $filterParts, $filterValue);
            });
        }
        return $query;
    }

    protected function setRelatedCondition($query, $modelName, $fieldName, $filterValue)
    {
        return $query->matching($modelName, function(\Cake\ORM\Query $q) use ($fieldName, $filterValue) {
            return $this->setValueCondition($q, $fieldName, $filterValue);
        });
    }

    protected function setValueCondition($query, $fieldName, $value)
    {
        if (strlen(trim($value, '%')) === strlen($value)) {
            return $query->where([$fieldName => $value]);
        } else {
            return $query->like([$fieldName => $value]);
        }
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
                    if (is_bool($contextFromField)) {
                        $contextFromFieldText = sprintf('%s: %s', $field, $contextFromField ? 'true' : 'false');
                    } else {
                        $contextFromFieldText = $contextFromField;
                    }
                    $filteringContexts[] = [
                        'label' => Inflector::humanize($contextFromFieldText),
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
            $association = $this->Table->associations()->get($model);
            $associationType = $association->type();
            if ($associationType == 'oneToMany') {
                $fieldToExtract = $subField;
                $associatedTable = $association->getTarget();
                $query = $associatedTable->find()->rightJoin(
                    [$this->Table->getAlias() => $this->Table->getTable()],
                    [sprintf('%s.id = %s.%s', $this->Table->getAlias(), $associatedTable->getAlias(), $association->getForeignKey())]
                )
                ->where([
                    ["${field} IS NOT" => NULL]
                ]);
            } else if ($associationType == 'manyToOne') {
                $fieldToExtract = sprintf('%s.%s', Inflector::singularize(strtolower($model)), $subField);
                $query = $this->Table->find()->contain($model);
            } else {
                throw new Exception("Association ${associationType} not supported in CRUD Component");
            }
        } else {
            $fieldToExtract = $field;
            $query = $this->Table->find();
        }
        return $query->select([$field])
            ->distinct()
            ->extract($fieldToExtract)
            ->toList();
    }

    private function renderViewInVariable($templateRelativeName, $data)
    {
        $builder = new ViewBuilder();
        $builder->disableAutoLayout()->setTemplate("{$this->TableAlias}/{$templateRelativeName}");
        $view = $builder->build($data);
        return $view->render();
    }
}
