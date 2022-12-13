<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Database\Expression\QueryExpression;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;


class OutboxController extends AppController
{
    public $filterFields = ['scope', 'action', 'title', 'message'];
    public $quickFilterFields = ['scope', 'action', ['title' => true], ['message' => true]];
    public $containFields = ['Users'];

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->set('metaGroup', 'Administration');
    }


    public function index()
    {
        $this->CRUD->index([
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
            'contextFilters' => [
                'fields' => [
                    'scope',
                ]
            ],
            'contain' => $this->containFields
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function filtering()
    {
        $this->CRUD->filtering();
    }

    public function view($id)
    {
        $this->CRUD->view($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function delete($id=false)
    {
        $this->set('deletionTitle', __('Confirm message deletion'));
        if (!empty($id)) {
            $this->set('deletionText', __('Are you sure you want to delete message #{0}?', $id));
        } else {
            $this->set('deletionText', __('Are you sure you want to delete the selected messages?'));
        }
        $this->set('deletionConfirm', __('Delete'));
        $this->CRUD->delete($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function process($id)
    {
        $request = $this->Outbox->get($id, ['contain' => ['Users' => ['Individuals' => ['Alignments' => 'Organisations']]]]);
        $scope = $request->scope;
        $action = $request->action;
        $this->outboxProcessors = TableRegistry::getTableLocator()->get('OutboxProcessors');
        $processor = $this->outboxProcessors->getProcessor($scope, $action);
        if ($this->request->is('post')) {
            $processResult = $processor->process($id, $this->request->getData(), $request);
            return $processor->genHTTPReply($this, $processResult);
        } else {
            $renderedView = $processor->render($request, $this->request);
            return $this->response->withStringBody($renderedView);
        }
    }

    public function listProcessors()
    {
        $this->OutboxProcessors = TableRegistry::getTableLocator()->get('OutboxProcessors');
        $processors = $this->OutboxProcessors->listProcessors();
        if ($this->ParamHandler->isRest()) {
            return $this->RestResponse->viewData($processors, 'json');
        }
        $data = [];
        foreach ($processors as $scope => $scopedProcessors) {
            foreach ($scopedProcessors as $processor) {
                $data[] = [
                    'enabled' => $processor->enabled,
                    'scope' => $scope,
                    'action' => $processor->action,
                    'description' => isset($processor->getDescription) ? $processor->getDescription() : null,
                    'notice' => $processor->notice ?? null,
                    'error' => $processor->error ?? null,
                ];
            }
        }
        $this->set('data', $data);
    }

    public function createEntry($scope, $action)
    {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException(__('Only POST method is accepted'));
        }
        $entryData = [
            'user_id' => $this->ACL->getUser()['id'],
        ];
        $entryData['data'] = $this->request->getData() ?? [];
        $this->OutboxProcessors = TableRegistry::getTableLocator()->get('OutboxProcessors');
        $processor = $this->OutboxProcessors->getProcessor($scope, $action);
        $creationResult = $this->OutboxProcessors->createOutboxEntry($processor, $entryData);
        return $processor->genHTTPReply($this, $creationResult);
    }
}
