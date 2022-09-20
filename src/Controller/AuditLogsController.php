<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\ORM\TableRegistry;
use \Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Core\Configure;
use PhpParser\Node\Stmt\Echo_;

class AuditLogsController extends AppController
{
    public $filterFields = ['model_id', 'model', 'request_action', 'user_id', 'model_title'];
    public $quickFilterFields = ['model', 'request_action', 'model_title'];
    public $containFields = ['Users'];

    public function index()
    {
        $this->CRUD->index([
            'contain' => $this->containFields,
            'order' => ['AuditLogs.id' => 'DESC'],
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
            'afterFind' => function($data) {
                $request_ip = is_resource($data['request_ip']) ? stream_get_contents($data['request_ip']) : $data['request_ip'];
                $change = is_resource($data['change']) ? stream_get_contents($data['change']) : $data['change'];
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
