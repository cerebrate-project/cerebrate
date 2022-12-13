<?php
use Cake\ORM\TableRegistry;

require_once(ROOT . DS . 'libraries' . DS . 'default' . DS . 'InboxProcessors' . DS . 'GenericInboxProcessor.php'); 

class NotificationInboxProcessor extends GenericInboxProcessor
{
    protected $scope = 'Notification';
    protected $action = 'not-specified'; //overriden when extending
    protected $description = ''; // overriden when extending
    protected $registeredActions = [
        'DataChange'
    ];

    public function __construct($loadFromAction=false) {
        parent::__construct($loadFromAction);
    }

    public function create($requestData)
    {
        return parent::create($requestData);
    }
}

class DataChangeProcessor extends NotificationInboxProcessor implements GenericInboxProcessorActionI {
    public $action = 'DataChange';
    protected $description;

    public function __construct() {
        parent::__construct();
        $this->description = __('Notify when data has been changed');
        $this->severity = $this->Inbox::SEVERITY_PRIMARY;
    }

    protected function addValidatorRules($validator)
    {
        return $validator
            ->requirePresence('original', 'changed')
            ->isArray('original', __('The `original` data must be provided'))
            ->isArray('changed', __('The `changed` data must be provided'));
    }
    
    public function create($requestData) {
        $this->validateRequestData($requestData);
        return parent::create($requestData);
    }

    public function getViewVariables($request)
    {
        $request = $this->__decodeMoreFields($request);
        return [
            'data' => [
                'original' => $request->data['original'],
                'changed' => $request->data['changed'],
                'summaryTemplate' => $request->data['summaryTemplate'],
                'summaryMessage' => $request->data['summaryMessage'],
                'entityType' => $request->data['entityType'],
                'entityDisplayField' => $request->data['entityDisplayField'],
                'entityViewURL' => $request->data['entityViewURL'],
            ],
        ];
    }

    public function process($id, $requestData, $inboxRequest)
    {
        $this->discard($id, $requestData);
        return $this->genActionResult(
            [],
            true,
            __('Notification acknowledged'),
            []
        );
    }

    public function discard($id, $requestData)
    {
        return parent::discard($id, $requestData);
    }

    private function __decodeMoreFields($request)
    {
        $decodedRequest = $request;
        $decodedRequest->data['original'] = $this->__decodeMoreChangedFields($request->data['original']);
        $decodedRequest->data['changed'] = $this->__decodeMoreChangedFields($request->data['changed']);
        return $decodedRequest;
    }

    private function __decodeMoreChangedFields(array $fieldData): array
    {
        array_walk($fieldData, function(&$fieldValue, $fieldName) {
            if ($fieldName === 'meta_fields') {
                $fieldValue = json_decode($fieldValue, true);
            }
        });
        return $fieldData;
    }
}