<?php
use Cake\ORM\TableRegistry;

require_once(ROOT . DS . 'libraries' . DS . 'default' . DS . 'RequestProcessors' . DS . 'GenericRequestProcessor.php'); 

class BroodRequestProcessor extends GenericRequestProcessor
{
    protected $scope = 'Brood';
    protected $action = 'not-specified'; //overriden when extending
    protected $description = ''; // overriden when extending
    protected $registeredActions = [
        'ToolInterconnection',
    ];

    public function __construct($loadFromAction=false) {
        parent::__construct($loadFromAction);
    }

    public function create($requestData)
    {
        return parent::create($requestData);
    }
}

class ToolInterconnectionProcessor extends BroodRequestProcessor implements GenericProcessorActionI {
    public $action = 'ToolInterconnection';
    protected $description;

    public function __construct() {
        parent::__construct();
        $this->description = __('Handle tool interconnection request from other cerebrate instance');
        $this->Broods = TableRegistry::getTableLocator()->get('Broods');
    }

    protected function addValidatorRules($validator)
    {
        return $validator;
    }
    
    public function create($requestData) {
        $this->validateRequestData($requestData);
        $requestData['title'] = __('Cerebrate instance {0} requested interconnection for tool {1}', 'Insert brood name', 'Insert tool name');
        return parent::create($requestData);
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
            $connectionSuccessfull ? __('Interconnection for `{0}` created', 'Insert tool name') : __('Could interconnect tool `{0}`.', 'Insert tool name'),
            []
        );
    }

    public function discard($id, $requestData)
    {
        return parent::discard($id, $requestData);
    }
}
