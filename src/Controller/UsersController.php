<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\ORM\TableRegistry;
use \Cake\Database\Expression\QueryExpression;

class UsersController extends AppController
{
    public $filterFields = ['Individuals.uuid', 'username', 'Individuals.email', 'Individuals.first_name', 'Individuals.last_name'];
    public $quickFilterFields = ['Individuals.uuid', ['username' => true], ['Individuals.first_name' => true], ['Individuals.last_name' => true], 'Individuals.email'];
    public $containFields = ['Individuals', 'Roles', 'UserSettings'];

    public function index()
    {
        $this->CRUD->index([
            'contain' => $this->containFields,
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', $this->isAdmin ? 'Administration' : 'Cerebrate');
    }

    public function add()
    {
        $this->CRUD->add([
            'beforeSave' => function($data) {
                $this->Users->enrollUserRouter($data);
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $dropdownData = [
            'role' => $this->Users->Roles->find('list', [
                'sort' => ['name' => 'asc']
            ]),
            'individual' => $this->Users->Individuals->find('list', [
                'sort' => ['email' => 'asc']
            ])
        ];
        $this->set(compact('dropdownData'));
        $this->set('metaGroup', $this->isAdmin ? 'Administration' : 'Cerebrate');
    }

    public function view($id = false)
    {
        if (empty($id) || empty($this->ACL->getUser()['role']['perm_admin'])) {
            $id = $this->ACL->getUser()['id'];
        }
        $this->CRUD->view($id, [
            'contain' => ['Individuals' => ['Alignments' => 'Organisations'], 'Roles']
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', $this->isAdmin ? 'Administration' : 'Cerebrate');
    }

    public function edit($id = false)
    {
        if (empty($id) || empty($this->ACL->getUser()['role']['perm_admin'])) {
            $id = $this->ACL->getUser()['id'];
        }
        $params = [
            'get' => [
                'fields' => [
                    'id', 'individual_id', 'role_id', 'username', 'disabled'
                ]
            ],
            'removeEmpty' => [
                'password'
            ],
            'fields' => [
                'id', 'individual_id', 'username', 'disabled', 'password', 'confirm_password'
            ]
        ];
        if (!empty($this->ACL->getUser()['role']['perm_admin'])) {
            $params['fields'][] = 'role_id';
        }
        $this->CRUD->edit($id, $params);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $dropdownData = [
            'role' => $this->Users->Roles->find('list', [
                'sort' => ['name' => 'asc']
            ]),
            'individual' => $this->Users->Individuals->find('list', [
                'sort' => ['email' => 'asc']
            ])
        ];
        $this->set(compact('dropdownData'));
        $this->set('metaGroup', $this->isAdmin ? 'Administration' : 'Cerebrate');
        $this->render('add');
    }

    public function toggle($id, $fieldName = 'disabled')
    {
        $this->CRUD->toggle($id, $fieldName);
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
        $this->set('metaGroup', $this->isAdmin ? 'Administration' : 'Cerebrate');
    }

    public function login()
    {
        $result = $this->Authentication->getResult();
        // If the user is logged in send them away.
        if ($result->isValid()) {
            $target = $this->Authentication->getLoginRedirect() ?? '/instance/home';
            return $this->redirect($target);
        }
        if ($this->request->is('post') && !$result->isValid()) {
            $this->Flash->error(__('Invalid username or password'));
        }
        $this->viewBuilder()->setLayout('login');
    }

    public function logout()
    {
        $result = $this->Authentication->getResult();
        if ($result->isValid()) {
            $this->Authentication->logout();
            $this->Flash->success(__('Goodbye.'));
            return $this->redirect(\Cake\Routing\Router::url('/users/login'));
        }
    }

    public function settings()
    {
        $this->set('user', $this->ACL->getUser());
        $all = $this->Users->UserSettings->getSettingsFromProviderForUser($this->ACL->getUser()['id'], true);
        $this->set('settingsProvider', $all['settingsProvider']);
        $this->set('settings', $all['settings']);
        $this->set('settingsFlattened', $all['settingsFlattened']);
        $this->set('notices', $all['notices']);
    }

    public function register()
    {
        $this->InboxProcessors = TableRegistry::getTableLocator()->get('InboxProcessors');
        $processor = $this->InboxProcessors->getProcessor('User', 'Registration');
        $data = [
            'origin' => '127.0.0.1',
            'comment' => 'Hi there!, please create an account',
            'data' => [
                'username' => 'foobar',
                'email' => 'foobar@admin.test',
                'first_name' => 'foo',
                'last_name' => 'bar',
            ],
        ];
        $processorResult = $processor->create($data);
        return $processor->genHTTPReply($this, $processorResult, ['controller' => 'Inbox', 'action' => 'index']);
    }
}
