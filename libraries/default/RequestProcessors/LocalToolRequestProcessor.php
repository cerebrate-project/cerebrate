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
    protected $LocalTools;

    public function __construct($loadFromAction=false)
    {
        parent::__construct($loadFromAction);
        $this->Broods = TableRegistry::getTableLocator()->get('Broods');
        $this->LocalTools = TableRegistry::getTableLocator()->get('LocalTools');
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

    protected function getConnector($request)
    {
        try {
            $connectorClasses = $this->LocalTools->getConnectorByToolName($request->local_tool_name);
            if (!empty($connectorClasses)) {
                $connector = array_values($connectorClasses)[0];
            }
        } catch (Cake\Http\Exception\NotFoundException $e) {
            $connector = null;
        }
        return $connector;
    }

    protected function getConnectorMeta($request)
    {
        try {
            $connectorClasses = $this->LocalTools->getConnectorByToolName($request->local_tool_name);
            if (!empty($connectorClasses)) {
                $connectorMeta = $this->LocalTools->extractMeta($connectorClasses)[0];
            }
        } catch (Cake\Http\Exception\NotFoundException $e) {
            $connectorMeta = null;
        }
        return !is_null($connectorMeta) ? $connectorMeta : [];
    }

    protected function addBaseValidatorRules($validator)
    {
        return $validator
            ->requirePresence('toolName')
            ->notEmpty('toolName', 'A url must be provided')
            ->requirePresence('url') // url -> cerebrate_url
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
        $request->connector = $this->getConnectorMeta($request);
        return [
            'request' => $request,
            'progressStep' => 0,
        ];
    }

    public function process($id, $requestData, $inboxRequest)
    {
        /**
         * /!\ Should how should sent message be? be fire and forget? Only for delined?
         */
        $interConnectionResult = [];
        $remoteCerebrate = $this->getIssuerBrood($inboxRequest);
        $connector = $this->getConnector($inboxRequest);
        if (!empty($requestData['is_discard'])) { // -> declined
            $connectorResult = $this->declineConnection($connector, $remoteCerebrate, $inboxRequest['data']); // Fire-and-forget?
            $connectionSuccessfull = true;
            $resultTitle = __('Could not sent declined message to `{0}`\'s  for {1}', $inboxRequest['origin'], $inboxRequest['local_tool_name']);
            $errors = [];
            if ($connectionSuccessfull) {
                $resultTitle = __('Declined message successfully sent to `{0}`\'s for {1}', $inboxRequest['origin'], $inboxRequest['local_tool_name']);
                $this->discard($id, $inboxRequest);
            }
        } else {
            $connectorResult = $this->acceptConnection($connector, $remoteCerebrate, $inboxRequest['data']);
            $connectionSuccessfull = false;
            $connectionData = [];
            $resultTitle = __('Could not inter-connect `{0}`\'s {1}', $inboxRequest['origin'], $inboxRequest['local_tool_name']);
            $errors = [];
            if ($connectionSuccessfull) {
                $resultTitle = __('Interconnection for `{0}`\'s {1} created', $inboxRequest['origin'], $inboxRequest['local_tool_name']);
                $this->discard($id, $inboxRequest);
            }
        }
        return $this->genActionResult(
            $connectionData,
            $connectionSuccessfull,
            $resultTitle,
            $errors
        );
    }

    public function discard($id, $requestData)
    {
        return parent::discard($id, $requestData);
    }

    protected function acceptConnection($connector, $remoteCerebrate, $requestData)
    {
        $connectorResult = $connector->acceptConnection($requestData['data']);
        $connectorResult['toolName'] = $requestData->local_tool_name;
        $response = $this->sendAcceptedRequestToRemote($remoteCerebrate, $connectorResult);
        // change state if sending fails
        // add the entry to the outbox if sending fails.
        return $response;
    }

    protected function declineConnection($connector, $remoteCerebrate, $requestData)
    {
        $connectorResult = $connector->declineConnection($requestData['data']);
        $connectorResult['toolName'] = $requestData->local_tool_name;
        $response = $this->sendDeclinedRequestToRemote($remoteCerebrate, $connectorResult);
        return $response;
    }

    protected function sendAcceptedRequestToRemote($remoteCerebrate, $connectorResult)
    {
        $urlPath = '/inbox/createInboxEntry/LocalTool/AcceptedRequest';
        $response = $this->Inbox->sendRequest($remoteCerebrate, $urlPath, true, $connectorResult);
        return $response;
    }

    protected function sendDeclinedRequestToRemote($remoteCerebrate, $connectorResult)
    {
        $urlPath = '/inbox/createInboxEntry/LocalTool/DeclinedRequest';
        $response = $this->Inbox->sendRequest($remoteCerebrate, $urlPath, true, $connectorResult);
        return $response;
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
        $request->connector = $this->getConnectorMeta($request);
        return [
            'request' => $request,
            'progressStep' => 1,
        ];
    }

    public function process($id, $requestData, $inboxRequest)
    {
        $connector = $this->getConnector($request);
        $remoteCerebrate = $this->getIssuerBrood($request);
        $connectorResult = $this->finalizeConnection($connector, $remoteCerebrate, $requestData['data']);
        $connectionSuccessfull = false;
        $connectionData = [];
        $resultTitle = __('Could not finalize inter-connection for `{0}`\'s {1}', $requestData['origin'], $requestData['local_tool_name']);
        $errors = [];
        if ($connectionSuccessfull) {
            $resultTitle = __('Interconnection for `{0}`\'s {1} finalized', $requestData['origin'], $requestData['local_tool_name']);
            $this->discard($id, $requestData);
        }
        return $this->genActionResult(
            $connectionData,
            $connectionSuccessfull,
            $resultTitle,
            $errors
        );
    }

    public function discard($id, $requestData)
    {
        return parent::discard($id, $requestData);
    }

    protected function finalizeConnection($connector, $remoteCerebrate, $requestData)
    {
        $connectorResult = $connector->finaliseConnection($requestData['data']);
        return $connectorResult;
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
        $request->connector = $this->getConnectorMeta($request);
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

    public function process($id, $requestData, $inboxRequest)
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
