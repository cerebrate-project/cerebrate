<?php
$defaultSteps = [
    [
        'text' => __('Request Sent'),
        'icon' => 'paper-plane',
        'title' => __(''),
        'confirmButton' => __('Accept Request'),
        'canDiscard' => true,
    ],
    [
        'text' => __('Request Accepted'),
        'icon' => 'check-square',
        'title' => __(''),
        'confirmButton' => __('Finalize Connection')
    ],
    [
        'text' => __('Connection done'),
        'icon' => 'exchange-alt',
        'title' => __(''),
    ]
];

$footerButtons = [];

$progressVariant = !empty($progressVariant) ? $progressVariant : 'info';
$finalSteps = array_replace($defaultSteps, $steps ?? []);
$currentStep = $finalSteps[$progressStep];
$progress = $this->Bootstrap->progressTimeline([
    'variant' => $progressVariant,
    'selected' => !empty($progressStep) ? $progressStep : 0,
    'steps' => $finalSteps,
]);

$footerButtons[] = [
    'clickFunction' => 'cancel',
    'variant' => 'secondary',
    'text' => __('Cancel'),
];
if (!empty($currentStep['canDiscard'])) {
    $footerButtons[] = [
        'clickFunction' => 'discard',
        'variant' => 'danger',
        'text' => __('Decline Request'),
    ];
}
$footerButtons[] = [
    'clickFunction' => 'accept',
    'text' => $currentStep['confirmButton'] ??  __('Submit'),
];

$table = $this->Bootstrap->table(['small' => true, 'bordered' => false, 'striped' => false, 'hover' => false], [
    'fields' => [
        ['key' => 'connector', 'label' => __('Tool Name'), 'formatter' => function($connector, $row) {
            return sprintf('<a href="%s" target="_blank">%s</a>',
                $this->Url->build(['controller' => 'localTools', 'action' => 'viewConnector', $connector['name']]),
                sprintf('%s (v%s)', h($connector['name']), h($connector['connector_version']))
            );
        }],
        ['key' => 'created', 'label' => __('Date'), 'formatter' => function($value, $row) {
            return $value->i18nFormat('yyyy-MM-dd HH:mm:ss');
        }],
        ['key' => 'origin', 'label' => __('Origin')],
        ['key' => 'brood', 'label' => __('Brood'), 'formatter' => function($brood, $row) {
            return sprintf('<a href="%s" target="_blank">%s</a>',
                $this->Url->build(['controller' => 'broods', 'action' => 'view', $brood['id']]),
                h($brood['name'])
            );
        }]
    ],
    'items' => [$request->toArray()],
]);
$form = $this->element('genericElements/Form/genericForm', [
    'entity' => null,
    'ajax' => false,
    'raw' => true,
    'data' => [
        'model' => 'Inbox',
        'fields' => [
            [
                'field' => 'is_discard',
                'type' => 'checkbox',
                'default' => false
            ]
        ],
        'submit' => [
            'action' => $this->request->getParam('action')
        ]
    ]
]);

$requestData = $this->Bootstrap->collapse(
    [
        'title' => __('Inter-connection data'),
        'open' => true,
    ],
    sprintf('<pre class="p-2 rounded mb-0" style="background: #eee;"><code>%s</code></pre>', json_encode($request['data'], JSON_PRETTY_PRINT))
);

$bodyHtml = sprintf('<div class="py-2"><div>%s</div>%s</div><div class="d-none">%s</div>',
    $table,
    $requestData,
    $form
);

echo $this->Bootstrap->modal([
    'title' => __('Interconnection Request for {0}', h($request->data['toolName'])),
    'size' => 'lg',
    'type' => 'custom',
    'bodyHtml' => sprintf('<div class="p-3">%s</div><div class="description-container">%s</div>',
        $progress,
        $bodyHtml
    ),
    'footerButtons' => $footerButtons
]);

?>

<script>
    function accept(modalObject, tmpApi) {
        const $form = modalObject.$modal.find('form')
        return tmpApi.postForm($form[0])
    }
    function discard(modalObject, tmpApi) {
        const $form = modalObject.$modal.find('form')
        const $discardField = $form.find('input#is_discard-field')
        $discardField.prop('checked', true)
        return tmpApi.postForm($form[0])
    }
    function cancel(modalObject, tmpApi) {
    }
</script>