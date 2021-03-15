<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\Database\Expression\QueryExpression;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;


class InboxController extends AppController
{
    public $filters = ['scope', 'action', 'title', 'origin', 'comment'];

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->set('metaGroup', 'Administration');
    }


    public function index()
    {
        $this->CRUD->index([
            'filters' => $this->filters,
            'quickFilters' => ['scope', 'action', ['title' => true], ['comment' => true]],
            'contextFilters' => [
                'fields' => [
                    'scope',
                    'action',
                ]
            ],
            'contain' => ['Users']
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

    // public function add()
    // {
    //     $this->CRUD->add();
    //     $responsePayload = $this->CRUD->getResponsePayload();
    //     if (!empty($responsePayload)) {
    //         return $responsePayload;
    //     }
    // }

    public function view($id)
    {
        $this->CRUD->view($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function delete($id)
    {
        $this->CRUD->delete($id);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
    }

    public function process($id)
    {
        $request = $this->Inbox->get($id);
        $scope = $request->scope;
        $action = $request->action;
        $processor = $this->Inbox->getRequestProcessor($scope, $action);
        if ($this->request->is('post')) {
            $processResult = $processor->process($id, $this->request);
            if ($processResult['success']) {
                $message = !empty($processResult['message']) ? $processResult['message'] : __('Request {0} processed.', $id);
                $response = $this->RestResponse->ajaxSuccessResponse('RequestProcessor', "{$scope}.{$action}", $processResult['data'], $message);
            } else {
                $message = !empty($processResult['message']) ? $processResult['message'] : __('Request {0} could not be processed.', $id);
                $response = $this->RestResponse->ajaxFailResponse('RequestProcessor', "{$scope}.{$action}", $processResult['data'], $message, $processResult['errors']);
            }
            return $response;
        } else {
            $processor->setViewVariables($this, $request);
            $processingTemplate = $processor->getProcessingTemplate();
            $this->set('request', $request);
            $this->viewBuilder()->setLayout('ajax');
            $this->render($processingTemplate);
        }
    }
}
