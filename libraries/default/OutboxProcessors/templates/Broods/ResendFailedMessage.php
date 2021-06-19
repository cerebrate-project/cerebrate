<?php
$footerButtons = [
    [
        'clickFunction' => 'cancel',
        'variant' => 'secondary',
        'text' => __('Cancel'),
    ],
    [
        'clickFunction' => 'deleteEntry',
        'variant' => 'danger',
        'text' => __('Delete Message'),
    ],
    [
        'clickFunction' => 'resendMessage',
        'text' => __('Re-Send Message'),
    ]
];

$tools = sprintf(
'<div class="mx-auto mb-3 mw-75 d-flex align-items-center">
    <span class="flex-grow-1 text-right" style="font-size: large;">%s</span>
    <span class="mx-3">%s</span>
    <span class="flex-grow-1 text-left" style="font-size: large;">%s</span>
</div>', 
    sprintf('<span class="mr-2 d-inline-flex flex-column"><a href="%s" target="_blank" title="%s">%s</a><i style="font-size: medium;" class="text-center">%s</i></span>',
        sprintf('/localTools/view/%s', h($request['localTool']->id)),
        h($request['localTool']->description),
        h($request['localTool']->name),
        __('(local tool)')
    ),
    sprintf('<i class="%s fa-lg"></i>', $this->FontAwesome->getClass('long-arrow-alt-right')),
    sprintf('<span class="ml-2 d-inline-flex flex-column"><a href="%s" target="_blank" title="%s">%s</a><i style="font-size: medium;" class="text-center">%s</i></span>',
        sprintf('/localTools/broodTools/%s', h($request['data']['remote_tool']['id'])),
        h($request['data']['remote_tool']['description']),
        h($request['data']['remote_tool']['name']),
        __('(remote tool)')
    )
);


$table = $this->Bootstrap->table(['small' => true, 'bordered' => false, 'striped' => false, 'hover' => false], [
    'fields' => [
        ['key' => 'created', 'label' => __('Date'), 'formatter' => function($value, $row) {
            return $value->i18nFormat('yyyy-MM-dd HH:mm:ss');
        }],
        ['key' => 'brood', 'label' => __('Brood'), 'formatter' => function($brood, $row) {
            return sprintf('<a href="%s" target="_blank">%s</a>',
                $this->Url->build(['controller' => 'broods', 'action' => 'view', $brood['id']]),
                h($brood['name'])
            );
        }],
        ['key' => 'individual', 'label' => __('Individual'), 'formatter' => function($individual, $row) {
            return sprintf('<a href="%s" target="_blank">%s</a>',
                $this->Url->build(['controller' => 'users', 'action' => 'view', $individual['id']]),
                h($individual['email'])
            );
        }],
        ['key' => 'individual.alignments', 'label' => __('Alignment'), 'formatter' => function($alignments, $row) {
            $html = '';
            foreach ($alignments as $alignment) {
                $html .= sprintf('<div class="text-nowrap"><b>%s</b> @ <a href="%s" target="_blank">%s</a></div>',
                    h($alignment['type']),
                    $this->Url->build(['controller' => 'users', 'action' => 'view', $alignment['organisation']['id']]),
                    h($alignment['organisation']['name'])
                );
            }
            return $html;
        }],
    ],
    'items' => [$request->toArray()],
]);

$requestData = $this->Bootstrap->collapse([
        'title' => __('Message data'),
        'open' => true,
    ],
    sprintf('<pre class="p-2 rounded mb-0" style="background: #eeeeee55;"><code>%s</code></pre>', json_encode($request['data']['sent'], JSON_PRETTY_PRINT))
);

$rows = sprintf('<tr><td class="font-weight-bold">%s</td><td>%s</td></tr>', __('URL'), h($request['data']['url']));
$rows .= sprintf('<tr><td class="font-weight-bold">%s</td><td>%s</td></tr>', __('Reason'), h($request['data']['reason']['message']) ?? '');
$rows .= sprintf('<tr><td class="font-weight-bold">%s</td><td>%s</td></tr>', __('Errors'), h(json_encode($request['data']['reason']['errors'])) ?? '');
$table2 = sprintf('<table class="table table-sm table-striped"><tbody>%s</tbody></table>', $rows);

$form = $this->element('genericElements/Form/genericForm', [
    'entity' => null,
    'ajax' => false,
    'raw' => true,
    'data' => [
        'model' => 'Inbox',
        'fields' => [
            [
                'field' => 'is_delete',
                'type' => 'checkbox',
                'default' => false
            ]
        ],
        'submit' => [
            'action' => $this->request->getParam('action')
        ]
    ]
]);
$form = sprintf('<div class="d-none">%s</div>', $form);

$bodyHtml = sprintf('<div><div>%s</div><div>%s</div><div>%s</div>%s</div>%s',
    $tools,
    $table,
    $table2,
    $requestData,
    $form
);

echo $this->Bootstrap->modal([
    'title' => $request['title'],
    'size' => 'xl',
    'type' => 'custom',
    'bodyHtml' => sprintf('<div class="p-3">%s</div>',
        $bodyHtml
    ),
    'footerButtons' => $footerButtons
]);

?>

<script>
    function resendMessage(modalObject, tmpApi) {
        const $form = modalObject.$modal.find('form')
        return tmpApi.postForm($form[0])
    }
    function deleteEntry(modalObject, tmpApi) {
        const $form = modalObject.$modal.find('form')
        const $discardField = $form.find('input#is_delete-field')
        $discardField.prop('checked', true)
        return tmpApi.postForm($form[0])
    }
    function cancel(modalObject, tmpApi) {
    }
</script>