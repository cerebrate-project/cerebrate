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
            $this->Controller->set('data', $data);
        }
    }

    private function getMetaTemplates()
    {
        $metaFields = [];
        if (!empty($this->Table->metaFields)) {
            $metaQuery = $this->MetaTemplates->find();
            $metaQuery->where([
                'scope' => $this->Table->metaFields,
                'enabled' => 1
            ]);
            $metaQuery->contain(['MetaTemplateFields']);
            $metaTemplates = $metaQuery->all();
            foreach ($metaTemplates as $metaTemplate) {
                foreach ($metaTemplate->meta_template_fields as $field) {
                    $metaFields[$field['field']] = $field;
                }
            }
        }
        $this->Controller->set('metaFields', $metaFields);
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
            if ($this->Table->save($data)) {
                $message = __('{0} added.', $this->ObjectAlias);
                if (!empty($input['metaFields'])) {
                    $this->saveMetaFields($data->id, $input);
                }
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
                    if (empty($params['redirect'])) {
                        $this->Controller->redirect(['action' => 'view', $data->id]);
                    } else {
                        $this->Controller->redirect($params['redirect']);
                    }
                }
            } else {
                $validationMessage = $this->prepareValidationError($data);
                $message = __(
                    '{0} could not be added.{1}',
                    $this->ObjectAlias,
                    empty($validationMessage) ? '' : ' ' . __('Reason:{0}', $validationMessage)
                );
                if ($this->Controller->ParamHandler->isRest()) {

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
            foreach ($params['removeEmpty'] as $removeEmptyField)
            if (isset($input[$removeEmptyField])) {
                unset($input[$removeEmptyField]);
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
            if ($this->Table->save($data)) {
                $message = __('{0} updated.', $this->ObjectAlias);
                if (!empty($input['metaFields'])) {
                    $this->MetaFields->deleteAll(['scope' => $this->Table->metaFields, 'parent_id' => $data->id]);
                    $this->saveMetaFields($data->id, $input);
                }
                if ($this->Controller->ParamHandler->isRest()) {
                    $this->Controller->restResponsePayload = $this->RestResponse->viewData($data, 'json');
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

                } else {
                    $this->Controller->Flash->error($message);
                }
            }
        }
        $this->Controller->set('entity', $data);
    }

    public function getMetaFields($id, $data)
    {
        if (empty($this->Table->metaFields)) {
            return $data;
        }
        $query = $this->MetaFields->find();
        $query->where(['scope' => $this->Table->metaFields, 'parent_id' => $id]);
        $metaFields = $query->all();
        $data['metaFields'] = [];
        foreach($metaFields as $metaField) {
            $data['metaFields'][$metaField->field] = $metaField->value;
        }
        return $data;
    }

    public function view(int $id, array $params = []): void
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid {0}.', $this->ObjectAlias));
        }

        $data = $this->Table->get($id, $params);
        $data = $this->getMetaFields($id, $data);
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
}
