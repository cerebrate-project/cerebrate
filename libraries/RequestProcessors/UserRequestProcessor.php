<?php
use Cake\ORM\TableRegistry;

require_once(ROOT . DS . 'libraries' . DS . 'RequestProcessors' . DS . 'GenericRequestProcessor.php'); 

class UserRequestProcessor extends GenericRequestProcessor
{
    protected $scope = 'User';
    protected $action = 'overridden-in-processor-action';
    protected $description = 'overridden-in-processor-action';
    protected $registeredActions = [
        'Registration'
    ];

    public function __construct($loadFromAction=false) {
        parent::__construct($loadFromAction);
    }

    public function create($requestData)
    {
        $requestData['scope'] = $this->scope;
        $requestData['action'] = $this->action;
        $requestData['description'] = $this->description;
        parent::create($requestData);
    }
}

class RegistrationProcessor extends UserRequestProcessor implements GenericProcessorActionI {
    protected $action = 'Registration';
    protected $description;

    public function __construct() {
        parent::__construct();
        $this->description = __('Handle user account for this cerebrate instance');
        $this->Users = TableRegistry::getTableLocator()->get('Users');
    }

    protected function addValidatorRules($validator)
    {
        return $validator
            ->notEmpty('username', 'A username must be provided.')
            ->add('email', 'validFormat', [
                'rule' => 'email',
                'message' => 'E-mail must be valid'
            ])
            ->notEmpty('first_name', 'A first name must be provided')
            ->notEmpty('last_name', 'A last name must be provided');
    }
    
    public function create($requestData) {
        $this->validateRequestData($requestData);
        $requestData['title'] = __('User account creation requested for {0}', $requestData['data']['email']);
        parent::create($requestData);
    }

    public function setViewVariables($controller, $request)
    {
        $dropdownData = [
            'role' => $this->Users->Roles->find('list', [
                'sort' => ['name' => 'asc']
            ]),
            'individual' => [-1 => __('-- New individual --')] + $this->Users->Individuals->find('list', [
                'sort' => ['email' => 'asc']
            ])->toArray()
        ];
        $individualEntity = $this->Users->Individuals->newEntity([
            'email' => !empty($request['data']['email']) ? $request['data']['email'] : '',
            'first_name' => !empty($request['data']['first_name']) ? $request['data']['first_name'] : '',
            'last_name' => !empty($request['data']['last_name']) ? $request['data']['last_name'] : '',
            'position' => !empty($request['data']['position']) ? $request['data']['position'] : '',
        ]);
        $userEntity = $this->Users->newEntity([
            'individual_id' => -1,
            'username' => !empty($request['data']['username']) ? $request['data']['username'] : '',
            'role_id' => !empty($request['data']['role_id']) ? $request['data']['role_id'] : '',
            'disabled' => !empty($request['data']['disabled']) ? $request['data']['disabled'] : '',
        ]);
        $controller->set('individualEntity', $individualEntity);
        $controller->set('userEntity', $userEntity);
        $controller->set(compact('dropdownData'));
    }

    public function process($id, $serverRequest)
    {
        $data = $serverRequest->getData();
        if ($data['individual_id'] == -1) {
            $individual = $this->Users->Individuals->newEntity([
                'uuid' => $data['uuid'],
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'position' => $data['position'],
            ]);
            $individual = $this->Users->Individuals->save($individual);
        } else {
            $individual = $this->Users->Individuals->get($data['individual_id']);
        }
        $user = $this->Users->newEntity([
            'individual_id' => $individual->id,
            'username' => $data['username'],
            'password' => '~PASSWORD_TO_BE_REPLACED~',
            'role_id' => $data['role_id'],
            'disabled' => $data['disabled'],
        ]);
        $user = $this->Users->save($user);
        return [
            'data' => $user,
            'success' => $user !== false,
            'message' => $user !== false ? __('User `{0}` created', $user->username) : __('Could not create user `{0}`.', $user->username),
            'errors' => $user->getErrors()
        ];
    }

    public function discard($id)
    {
        parent::discard($id);
    }
}