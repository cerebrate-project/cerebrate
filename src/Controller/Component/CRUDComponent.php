<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Error\Debugger;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\View\ViewBuilder;
use Cake\ORM\TableRegistry;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;

class CRUDComponent extends Component
{
    public $components = ['RestResponse'];

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
        $options['filters'][] = 'filteringLabel';
        if ($this->taggingSupported()) {
            $options['filters'][] = 'filteringTags';
        }

        $optionFilters = empty($options['filters']) ? [] : $options['filters'];
        foreach ($optionFilters as $i => $filter) {
            $optionFilters[] = "{$filter} !=";
        }
        $params = $this->Controller->ParamHandler->harvestParams($optionFilters);
        $query = $this->Table->find();
        if (!empty($options['filterFunction'])) {
            $query = $options['filterFunction']($query);
        }
        $query = $this->setFilters($params, $query, $options);
        $query = $this->setQuickFilters($params, $query, empty($options['quickFilters']) ? [] : $options['quickFilters']);
        if (!empty($options['conditions'])) {
            $query->where($options['conditions']);
        }
        if (!empty($options['contain'])) {
            $query->contain($options['contain']);
        }
        if ($this->taggingSupported()) {
            $query->contain('Tags');
        }
        if (!empty($options['fields'])) {
            $query->select($options['fields']);
        }
        if ($this->Controller->ParamHandler->isRest()) {
            $data = $query->all();
            if (isset($options['hidden'])) {
                $data->each(function($value, $key) use ($options) {
                    $hidden = is_array($options['hidden']) ? $options['hidden'] : [$options['hidden']];
                    $value->setHidden($hidden);
                    return $value;
                });
            }
            if (isset($options['afterFind'])) {
                $function = $options['afterFind'];
                if (is_callable($options['afterFind'])) {
                    $function = $options['afterFind'];
                    $data->each(function($value, $key) use ($function) {
                        return $function($value);
                    });
                } else {
                    $t = $this->Table;
                    $data->each(function($value, $key) use ($t, $function) {
                        return $t->$function($value);
                    });
                }
            }
            $this->Controller->restResponsePayload = $this->RestResponse->viewData($data, 'json');
        } else {
            $this->Controller->loadComponent('Paginator');
            $data = $this->Controller->Paginator->paginate($query, $this->Controller->paginate ?? []);
            if (isset($options['afterFind'])) {
                $function = $options['afterFind'];
                if (is_callable($options['afterFind'])) {
                    $function = $options['afterFind'];
                    $data->each(function($value, $key) use ($function) {
                        return $function($value);
                    });
                } else {
                    $t = $this->Table;
                    $data->each(function($value, $key) use ($t, $function) {
                        return $t->$function($value);
                    });
                }
            }
            $this->setFilteringContext($options['contextFilters'] ?? [], $params);
            $this->Controller->set('data', $data);
        }
    }

    public function filtering(): void
    {
        if ($this->taggingSupported()) {
            $this->Controller->set('taggingEnabled', true);
            $this->setAllTags();
        }
        $filters = !empty($this->Controller->filterFields) ? $this->Controller->filterFields : [];
        $this->Controller->set('filters', $filters);
        $this->Controller->viewBuilder()->setLayout('ajax');
        $this->Controller->render('/genericTemplates/filters');
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
        if ($this->request->is('post')) {
            $patchEntityParams = [
                'associated' => [],
                'accessibleFields' => $data->getAccessibleFieldForNew(),
            ];
            if (!empty($params['id'])) {
                unset($params['id']);
            }
            $input = $this->__massageInput($params);
            if (!empty($params['fields'])) {
                $patchEntityParams['fields'] = $params['fields'];
            }
            $data = $this->Table->patchEntity($data, $input, $patchEntityParams);
            if (isset($params['beforeSave'])) {
                $data = $params['beforeSave']($data);
                if ($data === false) {
                    throw new NotFoundException(__('Could not save {0} due to the input failing to meet expectations. Your input is bad and you should feel bad.', $this->ObjectAlias));
                }
            }
            $savedData = $this->Table->save($data);
            if ($savedData !== false) {
                if (isset($params['afterSave'])) {
                    $params['afterSave']($data);
                }
                $message = __('{0} added.', $this->ObjectAlias);
                if (!empty($input['metaFields'])) {
                    $this->saveMetaFields($data->id, $input);
                }
                if ($this->Controller->ParamHandler->isRest()) {
                    $this->Controller->restResponsePayload = $this->RestResponse->viewData($savedData, 'json');
                } else if ($this->Controller->ParamHandler->isAjax()) {
                    if (!empty($params['displayOnSuccess'])) {
                        $displayOnSuccess = $this->renderViewInVariable($params['displayOnSuccess'], ['entity' => $data]);
                        $this->Controller->ajaxResponsePayload = $this->RestResponse->ajaxSuccessResponse($this->ObjectAlias, 'add', $savedData, $message, ['displayOnSuccess' => $displayOnSuccess]);
                    } else {
                        $this->Controller->ajaxResponsePayload = $this->RestResponse->ajaxSuccessResponse($this->ObjectAlias, 'add', $savedData, $message);
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
                $validationErrors = $data->getErrors();
                $validationMessage = $this->prepareValidationMessage($validationErrors);
                $message = __(
                    '{0} could not be added.{1}',
                    $this->ObjectAlias,
                    empty($validationMessage) ? '' : PHP_EOL . __('Reason:{0}', $validationMessage)
                );
                if ($this->Controller->ParamHandler->isRest()) {
                    $this->Controller->restResponsePayload = $this->RestResponse->viewData($message, 'json');
                } else if ($this->Controller->ParamHandler->isAjax()) {
                    $this->Controller->ajaxResponsePayload = $this->RestResponse->ajaxFailResponse($this->ObjectAlias, 'add', $data, $message, $validationErrors);
                } else {
                    $this->Controller->Flash->error($message);
                }
            }
        }
        if (!empty($params['fields'])) {
            $this->Controller->set('fields', $params['fields']);
        }
        $this->Controller->entity = $data;
        $this->Controller->set('entity', $data);
    }

    public function prepareValidationMessage($errors)
    {
        $validationMessage = '';
        if (!empty($errors)) {
            if (count($errors) == 1) {
                $field = array_keys($errors)[0];
                $fieldError = implode(', ', array_values($errors[$field]));
                $validationMessage = __('{0}: {1}', $field, $fieldError);
            } else {
                $validationMessage = __('There has been validation issues with multiple fields');
            }
        }
        return $validationMessage;
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
                $validationMessage .= __('{0}: {1}', $field, implode(',', $errorMessages));
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
        if ($this->taggingSupported()) {
            $params['contain'][] = 'Tags';
            $this->setAllTags();
        }
        $data = $this->Table->find()->where(['id' => $id]);
        if (!empty($params['conditions'])) {
            $data->where($params['conditions']);
        }
        $data = $data->first();
        if (isset($params['afterFind'])) {
            $data = $params['afterFind']($data, $params);
        }
        if (empty($data)) {
            throw new NotFoundException(__('Invalid {0}.', $this->ObjectAlias));
        }
        $data = $this->getMetaFields($id, $data);
        if ($this->request->is(['post', 'put'])) {
            $patchEntityParams = [
                'associated' => []
            ];
            $input = $this->__massageInput($params);
            if (!empty($params['fields'])) {
                $patchEntityParams['fields'] = $params['fields'];
            }
            $data = $this->Table->patchEntity($data, $input, $patchEntityParams);
            if (isset($params['beforeSave'])) {
                $data = $params['beforeSave']($data);
            }
            $savedData = $this->Table->save($data);
            if ($savedData !== false) {
                if (isset($params['afterSave'])) {
                    $params['afterSave']($data);
                }
                $message = __('{0} `{1}` updated.', $this->ObjectAlias, $savedData->{$this->Table->getDisplayField()});
                if (!empty($input['metaFields'])) {
                    $this->MetaFields->deleteAll(['scope' => $this->Table->metaFields, 'parent_id' => $savedData->id]);
                    $this->saveMetaFields($savedData->id, $input);
                }
                if ($this->Controller->ParamHandler->isRest()) {
                    $this->Controller->restResponsePayload = $this->RestResponse->viewData($savedData, 'json');
                } else if ($this->Controller->ParamHandler->isAjax()) {
                    $this->Controller->ajaxResponsePayload = $this->RestResponse->ajaxSuccessResponse($this->ObjectAlias, 'edit', $savedData, $message);
                } else {
                    $this->Controller->Flash->success($message);
                    if (empty($params['redirect'])) {
                        $this->Controller->redirect(['action' => 'view', $id]);
                    } else {
                        $this->Controller->redirect($params['redirect']);
                    }
                }
            } else {
                $validationErrors = $data->getErrors();
                $validationMessage = $this->prepareValidationMessage($validationErrors);
                $message = __(
                    '{0} could not be modified.{1}',
                    $this->ObjectAlias,
                    empty($validationMessage) ? '' : PHP_EOL . __('Reason:{0}', $validationMessage)
                );
                if ($this->Controller->ParamHandler->isRest()) {
                } else if ($this->Controller->ParamHandler->isAjax()) {
                    $this->Controller->ajaxResponsePayload = $this->RestResponse->ajaxFailResponse($this->ObjectAlias, 'edit', $data, $message, $validationErrors);
                } else {
                    $this->Controller->Flash->error($message);
                }
            }
        }
        if (!empty($params['fields'])) {
            $this->Controller->set('fields', $params['fields']);
        }
        $this->Controller->entity = $data;
        $this->Controller->set('entity', $data);
    }

    public function attachMetaData($id, $data)
    {
        if (empty($this->Table->metaFields)) {
            return $data;
        }
        $metaFieldScope = $this->Table->metaFields;
        $query = $this->MetaTemplates->find()->where(['MetaTemplates.scope' => $metaFieldScope]);
        $query->contain(['MetaTemplateFields.MetaFields' => function ($q) use ($id, $metaFieldScope) {
            return $q->where(['MetaFields.scope' => $metaFieldScope, 'MetaFields.parent_id' => $id]);
        }]);
        $query
            ->order(['MetaTemplates.is_default' => 'DESC'])
            ->order(['MetaTemplates.name' => 'ASC']);
        $metaTemplates = $query->all()->toArray();
        $metaTemplates = $this->pruneEmptyMetaTemplates($metaTemplates);
        $data['metaTemplates'] = $metaTemplates;
        return $data;
    }

    public function pruneEmptyMetaTemplates($metaTemplates)
    {
        foreach ($metaTemplates as $i => $metaTemplate) {
            foreach ($metaTemplate['meta_template_fields'] as $j => $metaTemplateField) {
                if (empty($metaTemplateField['meta_fields'])) {
                    unset($metaTemplates[$i]['meta_template_fields'][$j]);
                }
            }
            if (empty($metaTemplates[$i]['meta_template_fields'])) {
                unset($metaTemplates[$i]);
            }
        }
        return $metaTemplates;
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

        if ($this->taggingSupported()) {
            $params['contain'][] = 'Tags';
            $this->setAllTags();
        }

        $data = $this->Table->get($id, $params);
        $data = $this->attachMetaData($id, $data);
        if (isset($params['afterFind'])) {
            $data = $params['afterFind']($data);
        }
        if ($this->Controller->ParamHandler->isRest()) {
            $this->Controller->restResponsePayload = $this->RestResponse->viewData($data, 'json');
        }
        $this->Controller->set('entity', $data);
    }

    public function delete($id=false, $params=[]): void
    {
        if ($this->request->is('get')) {
            if(!empty($id)) {
                $data = $this->Table->find()->where([$this->Table->getAlias() . '.id' => $id]);
                if (!empty($params['conditions'])) {
                    $data->where($params['conditions']);
                }
                if (!empty($params['contain'])) {
                    $data->contain($params['contain']);
                }
                $data = $data->first();
                if (empty($data)) {
                    throw new NotFoundException(__('Invalid {0}.', $this->ObjectAlias));
                }
                if (isset($params['beforeSave'])) {
                    $data = $params['beforeSave']($data);
                    if ($data === false) {
                        throw new NotFoundException(__('Could not save {0} due to the input failing to meet expectations. Your input is bad and you should feel bad.', $this->ObjectAlias));
                    }
                }
                $this->Controller->set('id', $data['id']);
                $this->Controller->set('data', $data);
                $this->Controller->set('bulkEnabled', false);
            } else {
                $this->Controller->set('bulkEnabled', true);
            }
        } else if ($this->request->is('post') || $this->request->is('delete')) {
            $ids = $this->getIdsOrFail($id);
            $isBulk = count($ids) > 1;
            $bulkSuccesses = 0;
            foreach ($ids as $id) {
                $skipExecution = false;
                $data = $this->Table->find()->where([$this->Table->getAlias() . '.id' => $id]);
                if (!empty($params['conditions'])) {
                    $data->where($params['conditions']);
                }
                if (!empty($params['contain'])) {
                    $data->contain($params['contain']);
                }
                $data = $data->first();
                if (isset($params['beforeSave'])) {
                    try {
                        $data = $params['beforeSave']($data);
                    } catch (Exception $e) {
                        $data = false;
                    }
                }
                if (!empty($data)) {
                    $success = $this->Table->delete($data);
                    $success = true;
                } else {
                    $success = false;
                }
                if ($success) {
                    $bulkSuccesses++;
                }
            }
            $message = $this->getMessageBasedOnResult(
                $bulkSuccesses == count($ids),
                $isBulk,
                __('{0} deleted.', $this->ObjectAlias),
                __('All {0} have been deleted.', Inflector::pluralize($this->ObjectAlias)),
                __('Could not delete {0}.', $this->ObjectAlias),
                __('{0} / {1} {2} have been deleted.',
                    $bulkSuccesses,
                    count($ids),
                    Inflector::pluralize($this->ObjectAlias)
                )
            );
            $this->setResponseForController('delete', $bulkSuccesses, $message, $data);
        }
        $this->Controller->set('metaGroup', 'ContactDB');
        $this->Controller->set('scope', 'users');
        $this->Controller->viewBuilder()->setLayout('ajax');
        $this->Controller->render('/genericTemplates/delete');
    }

    public function tag($id=false): void
    {
        if (!$this->taggingSupported()) {
            throw new Exception("Table {$this->TableAlias} does not support tagging");
        }
        if ($this->request->is('get')) {
            $this->setAllTags();
            if(!empty($id)) {
                $params = [
                    'contain' => 'Tags',
                ];
                $entity = $this->Table->get($id, $params);
                $this->Controller->set('id', $entity->id);
                $this->Controller->set('data', $entity);
                $this->Controller->set('bulkEnabled', false);
            } else {
                $this->Controller->set('bulkEnabled', true);
            }
        } else if ($this->request->is('post') || $this->request->is('delete')) {
            $ids = $this->getIdsOrFail($id);
            $isBulk = count($ids) > 1;
            $bulkSuccesses = 0;
            foreach ($ids as $id) {
                $params = [
                    'contain' => 'Tags',
                ];
                $entity = $this->Table->get($id, $params);
                $input = $this->request->getData();
                $tagsToAdd = json_decode($input['tag_list']);
                // patching will mirror tag in the DB, however, we only want to add tags
                $input['tags'] = array_merge($tagsToAdd, $entity->tags);
                $patchEntityParams = [
                    'fields' => ['tags'],
                ];
                $entity = $this->Table->patchEntity($entity, $input, $patchEntityParams);
                $savedData = $this->Table->save($entity);
                $success = true;
                if ($success) {
                    $bulkSuccesses++;
                }
            }
            $message = $this->getMessageBasedOnResult(
                $bulkSuccesses == count($ids),
                $isBulk,
                __('{0} tagged with `{1}`.', $this->ObjectAlias, $input['tag_list']),
                __('All {0} have been tagged.', Inflector::pluralize($this->ObjectAlias)),
                __('Could not tag {0} with `{1}`.', $this->ObjectAlias, $input['tag_list']),
                __('{0} / {1} {2} have been tagged.',
                    $bulkSuccesses,
                    count($ids),
                    Inflector::pluralize($this->ObjectAlias)
                )
            );
            $this->setResponseForController('tag', $bulkSuccesses, $message, $savedData);
        }
        $this->Controller->viewBuilder()->setLayout('ajax');
        $this->Controller->render('/genericTemplates/tagForm');
    }

    public function untag($id=false): void
    {
        if (!$this->taggingSupported()) {
            throw new Exception("Table {$this->TableAlias} does not support tagging");
        }
        if ($this->request->is('get')) {
            $this->setAllTags();
            if(!empty($id)) {
                $params = [
                    'contain' => 'Tags',
                ];
                $entity = $this->Table->get($id, $params);
                $this->Controller->set('id', $entity->id);
                $this->Controller->set('data', $entity);
                $this->Controller->set('bulkEnabled', false);
            } else {
                $this->Controller->set('bulkEnabled', true);
            }
        } else if ($this->request->is('post') || $this->request->is('delete')) {
            $ids = $this->getIdsOrFail($id);
            $isBulk = count($ids) > 1;
            $bulkSuccesses = 0;
            foreach ($ids as $id) {
                $params = [
                    'contain' => 'Tags',
                ];
                $entity = $this->Table->get($id, $params);
                $input = $this->request->getData();
                $tagsToRemove = json_decode($input['tag_list']);
                // patching will mirror tag in the DB, however, we only want to remove tags
                $input['tags'] = array_filter($entity->tags, function ($existingTag) use ($tagsToRemove) {
                    return !in_array($existingTag->name, $tagsToRemove);
                });
                $patchEntityParams = [
                    'fields' => ['tags'],
                ];
                $entity = $this->Table->patchEntity($entity, $input, $patchEntityParams);
                $savedData = $this->Table->save($entity);
                $success = true;
                if ($success) {
                    $bulkSuccesses++;
                }
            }
            $message = $this->getMessageBasedOnResult(
                $bulkSuccesses == count($ids),
                $isBulk,
                __('{0} untagged with `{1}`.', $this->ObjectAlias, implode(', ', $tagsToRemove)),
                __('All {0} have been untagged.', Inflector::pluralize($this->ObjectAlias)),
                __('Could not untag {0} with `{1}`.', $this->ObjectAlias, $input['tag_list']),
                __('{0} / {1} {2} have been untagged.',
                    $bulkSuccesses,
                    count($ids),
                    Inflector::pluralize($this->ObjectAlias)
                )
            );
            $this->setResponseForController('tag', $bulkSuccesses, $message, $entity);
        }
        $this->Controller->viewBuilder()->setLayout('ajax');
        $this->Controller->render('/genericTemplates/tagForm');
    }

    public function viewTags(int $id, array $params = []): void
    {
        if (!$this->taggingSupported()) {
            throw new Exception("Table {$this->TableAlias} does not support tagging");
        }
        if (empty($id)) {
            throw new NotFoundException(__('Invalid {0}.', $this->ObjectAlias));
        }

        $params['contain'][] = 'Tags';
        $data = $this->Table->get($id, $params);
        if (isset($params['afterFind'])) {
            $data = $params['afterFind']($data);
        }
        if ($this->Controller->ParamHandler->isRest()) {
            $this->Controller->restResponsePayload = $this->RestResponse->viewData($data, 'json');
        }
        $this->Controller->set('entity', $data);
        $this->setAllTags();
        $this->Controller->viewBuilder()->setLayout('ajax');
        $this->Controller->render('/genericTemplates/tag');
    }

    public function setResponseForController($action, $success, $message, $data=[], $errors=null)
    {
        if ($success) {
            if ($this->Controller->ParamHandler->isRest()) {
                $this->Controller->restResponsePayload = $this->RestResponse->viewData($data, 'json');
            } elseif ($this->Controller->ParamHandler->isAjax()) {
                $this->Controller->ajaxResponsePayload = $this->RestResponse->ajaxSuccessResponse($this->ObjectAlias, $action, $data, $message);
            } else {
                $this->Controller->Flash->success($message);
                $this->Controller->redirect($this->Controller->referer());
            }
        } else {
            if ($this->Controller->ParamHandler->isRest()) {
                $this->Controller->restResponsePayload = $this->RestResponse->viewData($data, 'json');
            } elseif ($this->Controller->ParamHandler->isAjax()) {
                $this->Controller->ajaxResponsePayload = $this->RestResponse->ajaxFailResponse($this->ObjectAlias, $action, $data, $message, !is_null($errors) ? $errors : $data->getErrors());
            } else {
                $this->Controller->Flash->error($message);
                $this->Controller->redirect($this->Controller->referer());
            }
        }
    }

    private function getMessageBasedOnResult($isSuccess, $isBulk, $messageSingleSuccess, $messageBulkSuccess, $messageSingleFailure, $messageBulkFailure)
    {
        if ($isSuccess) {
            $message = $isBulk ? $messageBulkSuccess : $messageSingleSuccess;
        } else {
            $message = $isBulk ? $messageBulkFailure : $messageSingleFailure;
        }
        return $message;
    }

    /**
     * getIdsOrFail
     *
     * @param  mixed $id
     * @return Array The ID converted to a list or the list of provided IDs from the request
     * @throws NotFoundException when no ID could be found
     */
    public function getIdsOrFail($id=false): Array
    {
        $params = $this->Controller->ParamHandler->harvestParams(['ids']);
        if (!empty($params['ids'])) {
            $params['ids'] = json_decode($params['ids']);
        }
        $ids = [];
        if (empty($id)) {
            if (empty($params['ids'])) {
                throw new NotFoundException(__('Invalid {0}.', $this->ObjectAlias));
            }
            $ids = $params['ids'];
        } else {
            $id = $this->getInteger($id);
            if (!is_null($id)) {
                $ids = [$id];
            } else {
                throw new NotFoundException(__('Invalid {0}.', $this->ObjectAlias));
            }
        }
        return $ids;
    }

    private function getInteger($value)
    {
        return is_numeric($value) ? intval($value) : null;
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

    public function setQuickFilters(array $params, \Cake\ORM\Query $query, array $quickFilterFields): \Cake\ORM\Query
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

    protected function setFilters($params, \Cake\ORM\Query $query, array $options): \Cake\ORM\Query
    {
        $filteringLabel = !empty($params['filteringLabel']) ? $params['filteringLabel'] : '';
        unset($params['filteringLabel']);
        $filteringTags = !empty($params['filteringTags']) && $this->taggingSupported() ? $params['filteringTags'] : '';
        unset($params['filteringTags']);
        $customFilteringFunction = '';
        $chosenFilter = '';
        if (!empty($options['contextFilters']['custom'])) {
            foreach ($options['contextFilters']['custom'] as $filter) {
                if ($filter['label'] == $filteringLabel) {
                    $customFilteringFunction = $filter;
                    $chosenFilter = $filter;
                    break;
                }
            }
        }

        $activeFilters = [];
        if (!empty($customFilteringFunction['filterConditionFunction'])) {
            $query = $customFilteringFunction['filterConditionFunction']($query);
            $activeFilters['filteringLabel'] = $filteringLabel;
        } else {
            if (!empty($chosenFilter)) {
                $params = $this->massageFilters($chosenFilter['filterCondition']);
            } else {
                $params = $this->massageFilters($params);
            }
            if (!empty($params['simpleFilters'])) {
                foreach ($params['simpleFilters'] as $filter => $filterValue) {
                    $activeFilters[$filter] = $filterValue;
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
                    $activeFilters[$filter] = $filterValue;
                    $filterParts = explode('.', $filter);
                    $query = $this->setNestedRelatedCondition($query, $filterParts, $filterValue);
                }
            }
        }

        if ($this->taggingSupported() && !empty($filteringTags)) {
            $activeFilters['filteringTags'] = $filteringTags;
            $query = $this->setTagFilters($query, $filteringTags);
        }

        $this->Controller->set('activeFilters', $activeFilters);
        return $query;
    }

    protected function setTagFilters($query, $tags)
    {
        $modelAlias = $this->Table->getAlias();
        $subQuery = $this->Table->find('tagged', [
            'name' => $tags,
            'forceAnd' => true
        ])->select($modelAlias . '.id');
        return $query->where([$modelAlias . '.id IN' => $subQuery]);
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
            return $query->where(function ($exp, \Cake\ORM\Query $q) use ($fieldName, $value) {
                return $exp->like($fieldName, $value);
            });
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
                        $contextFromFieldText = sprintf('%s: %s', $field, $contextFromField);
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

    public function setParentConditionsForMetaFields($query, array $metaConditions)
    {
        $metaTemplates = $this->MetaFields->MetaTemplates->find('list', [
            'keyField' => 'name',
            'valueField' => 'id'
        ])->where(['name IN' => array_keys($metaConditions)])->all()->toArray();
        $fieldsConditions = [];
        foreach ($metaConditions as $templateName => $templateConditions) {
            $metaTemplateID = isset($metaTemplates[$templateName]) ? $metaTemplates[$templateName] : -1;
            foreach ($templateConditions as $conditions) {
                $conditions['meta_template_id'] = $metaTemplateID;
                $fieldsConditions[] = $conditions;
            }
        }
        $matchingMetaQuery = $this->getParentIDQueryForMetaANDConditions($fieldsConditions);
        return $query->where(['id IN' => $matchingMetaQuery]);
    }

    private function getParentIDQueryForMetaANDConditions(array $metaANDConditions)
    {
        if (empty($metaANDConditions)) {
            throw new Exception('Invalid passed conditions');
        }
        foreach ($metaANDConditions as $i => $conditions) {
            $metaANDConditions[$i]['scope'] = $this->Table->metaFields;
        }
        $firstCondition = $this->prefixConditions('MetaFields', $metaANDConditions[0]);
        $conditionsToJoin = array_slice($metaANDConditions, 1);
        $query = $this->MetaFields->find()
            ->select('parent_id')
            ->where($firstCondition);
        foreach ($conditionsToJoin as $i => $conditions) {
            $joinedConditions = $this->prefixConditions("m{$i}", $conditions);
            $joinedConditions[] = "m{$i}.parent_id = MetaFields.parent_id";
            $query->rightJoin(
                ["m{$i}" => 'meta_fields'],
                $joinedConditions
            );
        }
        return $query;
    }

    private function prefixConditions(string $prefix, array $conditions)
    {
        $prefixedConditions = [];
        foreach ($conditions as $condField => $condValue) {
            $prefixedConditions["${prefix}.${condField}"] = $condValue;
        }
        return $prefixedConditions;
    }

    public function taggingSupported()
    {
        return $this->Table->behaviors()->has('Tag');
    }

    public function setAllTags()
    {
        $this->Tags = TableRegistry::getTableLocator()->get('Tags.Tags');
        $allTags = $this->Tags->find()->all()->toList();
        $this->Controller->set('allTags', $allTags);
    }

    public function toggle(int $id, string $fieldName = 'enabled', array $params = []): void
    {
        if (empty($id)) {
            throw new NotFoundException(__('Invalid {0}.', $this->ObjectAlias));
        }

        $data = $this->Table->get($id, $params);
        if (isset($params['afterFind'])) {
            $data = $params['afterFind']($data, $params);
        }
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
                    $this->Controller->ajaxResponsePayload = $this->RestResponse->ajaxSuccessResponse($this->ObjectAlias, 'toggle', $savedData, $message);
                } else {
                    $this->Controller->Flash->success($message);
                    if (empty($params['redirect'])) {
                        $this->Controller->redirect(['action' => 'view', $id]);
                    } else {
                        $this->Controller->redirect($params['redirect']);
                    }
                }
            } else {
                $validationErrors = $data->getErrors();
                $validationMessage = $this->prepareValidationMessage($validationErrors);
                $message = __(
                    '{0} could not be modified.{1}',
                    $this->ObjectAlias,
                    empty($validationMessage) ? '' : ' ' . __('Reason:{0}', $validationMessage)
                );
                if ($this->Controller->ParamHandler->isRest()) {
                } else if ($this->Controller->ParamHandler->isAjax()) {
                    $this->Controller->ajaxResponsePayload = $this->RestResponse->ajaxFailResponse($this->ObjectAlias, 'toggle', $message, $validationErrors);
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
            $this->Controller->restResponsePayload = $this->RestResponse->viewData(['value' => $data[$fieldName]], 'json');
        } else {
            if ($this->Controller->ParamHandler->isRest()) {
                $this->Controller->restResponsePayload = $this->RestResponse->viewData(['value' => $data[$fieldName]], 'json');
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
