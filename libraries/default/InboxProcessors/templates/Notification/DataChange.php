<?php
if (!empty($data['summary'])) {
    $changedSummary = h($data['summary']);
} else if (!empty($data['summaryTemplate']) && !empty($data['summaryMessage'])) {
    $changedSummary = __(
        sprintf('%s. %s.', h($data['summaryTemplate']), h($data['summaryMessage'])),
        h($data['entityType']),
        sprintf(
            '<a href="%s" target="_blank">%s</a>',
            h($data['entityViewURL']),
            h($data['entityDisplayField'])
        )
    );
} else {
    $changedSummary = '';
}

$form = $this->element('genericElements/Form/genericForm', [
    'entity' => null,
    'ajax' => false,
    'raw' => true,
    'data' => [
        'model' => 'Inbox',
        'fields' => [],
        'submit' => [
            'action' => $this->request->getParam('action')
        ]
    ]
]);

echo $this->Bootstrap->modal([
    'title' => __('Acknowledge notification'),
    'size' => 'xl',
    'type' => 'confirm',
    'bodyHtml' => sprintf(
        '<div class="d-none">%s</div>
        <div class="container">
            <div class="row">
                <p>%s</p>
                <div class="col">%s</div>
                <div class="col">%s</div>
            </div>
        </div>',
        $form,
        $changedSummary,
        $this->Bootstrap->card([
            'headerText' => __('Original values'),
            'bodyHTML' => $this->element('genericElements/SingleViews/Fields/jsonField', ['field' => ['raw' => $data['original']]])
        ]),
        $this->Bootstrap->card([
            'headerText' => __('Changed values'),
            'bodyHTML' => $this->element('genericElements/SingleViews/Fields/jsonField', ['field' => ['raw' => $data['changed']]])
        ])
    ),
    'confirmText' => __('Acknowledge & Discard'),
    'confirmIcon' => 'check',
]);
?>
</div>
