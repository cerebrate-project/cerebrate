<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\ORM\TableRegistry;
use \Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Core\Configure;

class AuditLogsController extends AppController
{
    public $filterFields = ['model_id', 'model', ['name' => 'request_action', 'multiple' => true, ], 'user_id', 'model_title', 'AuditLogs.created'];
    public $quickFilterFields = ['model', 'request_action', 'model_title'];
    public $containFields = ['Users'];

    public function index()
    {
        $this->CRUD->index([
            'contain' => $this->containFields,
            'order' => ['AuditLogs.id' => 'DESC'],
            'filters' => $this->CRUD->getFilterFieldsName($this->filterFields),
            'quickFilters' => $this->quickFilterFields,
            'afterFind' => function($data) {
                $request_ip = is_resource($data['request_ip']) ? stream_get_contents($data['request_ip']) : $data['request_ip'];
                $change = is_resource($data['changed']) ? stream_get_contents($data['changed']) : $data['changed'];
                $data['request_ip'] = inet_ntop($request_ip);
                $data['changed'] = $change;
                return $data;
            }
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
}
