<?php
use Cake\ORM\TableRegistry;
use Cake\Filesystem\File;

require_once(ROOT . DS . 'libraries' . DS . 'default' . DS . 'RequestProcessors' . DS . 'GenericRequestProcessor.php'); 

class LocalToolRequestProcessor extends GenericRequestProcessor
{
    protected $scope = 'LocalTool';
    protected $action = 'not-specified'; //overriden when extending
    protected $description = ''; // overriden when extending
    protected $registeredActions = [
        'IncomingConnectionRequest',
        'AcceptedRequest',
        'DeclinedRequest',
    ];
    protected $processingTemplate = 'LocalTool/GenericRequest';
    protected $Broods;

    public function __construct($loadFromAction=false)
    {
        parent::__construct($loadFromAction);
        $this->Broods = TableRegistry::getTableLocator()->get('Broods');
    }

    public function create($requestData)
    {
        return parent::create($requestData);
    }

    protected function assignProcessingTemplate($toolName)
    {
        $processingTemplatePath = sprintf('%s/%s/%s.php', $this->scope, $toolName, $this->action);
        $file = new File($this->processingTemplatesDirectory . DS . $processingTemplatePath);
        if ($file->exists()) {
            $this->processingTemplate = str_replace('.php', '', $processingTemplatePath);
        }
        $file->close();
    }

    protected function validateToolName($requestData)
    {
        if (empty($requestData['data']['toolName'])) {
            throw new Exception('Error while validating request data. Tool name is missing.');
        }
    }

    protected function getIssuerBrood($request)
    {
        $brood = $this->Broods->find()
            ->where(['url' => $request['origin']])
            ->first();
        return $brood;
    }

    protected function addBaseValidatorRules($validator)
    {
        return $validator
            ->requirePresence('toolName')
            ->notEmpty('toolName', 'A url must be provided')
            ->requirePresence('url')
            ->notEmpty('url', 'A url must be provided');
            // ->add('url', 'validFormat', [
            //     'rule' => 'url',
            //     'message' => 'URL must be valid'
            // ]);
    }
}

class IncomingConnectionRequestProcessor extends LocalToolRequestProcessor implements GenericProcessorActionI {
    public $action = 'IncomingConnectionRequest';
    protected $description;

    public function __construct() {
        parent::__construct();
        $this->description = __('Handle Phase I of inter-connection when another cerebrate instance performs the request.');
    }

    protected function addValidatorRules($validator)
    {
        return $this->addBaseValidatorRules($validator);
    }
    
    public function create($requestData) {
        $this->validateToolName($requestData);
        $this->validateRequestData($requestData);
        $requestData['title'] = __('Request for {0} Inter-connection', $requestData['local_tool_name']);
        return parent::create($requestData);
    }

    public function getViewVariables($request)
    {
        $request->brood = $this->getIssuerBrood($request);
        return [
            'request' => $request,
            'progressStep' => 0,
        ];
    }

    public function process($id, $requestData)
    {
        $connectionSuccessfull = false;
        $interConnectionResult = [];
        if ($connectionSuccessfull) {
            $this->discard($id, $requestData);
        }
        return $this->genActionResult(
            $interConnectionResult,
            $connectionSuccessfull,
            $connectionSuccessfull ? __('Interconnection for `{0}`\'s {1} created',$requestData['origin'], $requestData['local_tool_name']) : __('Could not inter-connect `{0}`\'s {1}', $requestData['origin'], $requestData['local_tool_name']),
            []
        );
    }

    public function discard($id, $requestData)
    {
        // /!\ TODO: send decline message to remote cerebrate
        return parent::discard($id, $requestData);
    }
}

class AcceptedRequestProcessor extends LocalToolRequestProcessor implements GenericProcessorActionI {
    public $action = 'AcceptedRequest';
    protected $description;

    public function __construct() {
        parent::__construct();
        $this->description = __('Handle Phase II of inter-connection when initial request has been accepted by the remote cerebrate.');
        // $this->Broods = TableRegistry::getTableLocator()->get('Broods');
    }

    protected function addValidatorRules($validator)
    {
        return $this->addBaseValidatorRules($validator);
    }
    
    public function create($requestData) {
        $this->validateToolName($requestData);
        $this->validateRequestData($requestData);
        $requestData['title'] = __('Inter-connection for {0} has been accepted', $requestData['local_tool_name']);
        return parent::create($requestData);
    }

    public function getViewVariables($request)
    {
        $request->brood = $this->getIssuerBrood($request);
        return [
            'request' => $request,
            'progressStep' => 1,
        ];
    }

    public function process($id, $requestData)
    {
        $connectionSuccessfull = false;
        $interConnectionResult = [];
        if ($connectionSuccessfull) {
            $this->discard($id, $requestData);
        }
        return $this->genActionResult(
            $interConnectionResult,
            $connectionSuccessfull,
            $connectionSuccessfull ? __('Interconnection for `{0}`\'s {1} finalized', $requestData['origin'], $requestData['local_tool_name']) : __('Could not inter-connect `{0}`\'s {1}', $requestData['origin'], $requestData['local_tool_name']),
            []
        );
    }

    public function discard($id, $requestData)
    {
        return parent::discard($id, $requestData);
    }
}

class DeclinedRequestProcessor extends LocalToolRequestProcessor implements GenericProcessorActionI {
    public $action = 'DeclinedRequest';
    protected $description;

    public function __construct() {
        parent::__construct();
        $this->description = __('Handle Phase II of MISP inter-connection when initial request has been declined by the remote cerebrate.');
    }

    protected function addValidatorRules($validator)
    {
        return $this->addBaseValidatorRules($validator);
    }
    
    public function create($requestData) {
        $this->validateToolName($requestData);
        $this->validateRequestData($requestData);
        $requestData['title'] = __('Declined inter-connection for {0}', $requestData['local_tool_name']);
        return parent::create($requestData);
    }

    public function getViewVariables($request)
    {
        $request->brood = $this->getIssuerBrood($request);
        return [
            'request' => $request,
            'progressStep' => 1,
            'progressVariant' => 'danger',
            'steps' => [
                1 => ['icon' => 'times', 'text' => __('Request Declined'), 'confirmButton' => __('Clean-up')],
                2 => ['icon' => 'trash', 'text' => __('Clean-up')],
            ]
        ];
    }

    public function process($id, $requestData)
    {
        $connectionSuccessfull = false;
        $interConnectionResult = [];
        if ($connectionSuccessfull) {
            $this->discard($id, $requestData);
        }
        return $this->genActionResult(
            $interConnectionResult,
            $connectionSuccessfull,
            $connectionSuccessfull ? __('Interconnection for `{0}`\'s {1} finalized', $requestData['origin'], $requestData['local_tool_name']) : __('Could not inter-connect `{0}`\'s {1}', $requestData['origin'], $requestData['local_tool_name']),
            []
        );
    }
    public function discard($id, $requestData)
    {
        return parent::discard($id, $requestData);
    }
}
