<?php
$randomIdOld = Cake\Utility\Security::randomString(8);
$randomIdNew = Cake\Utility\Security::randomString(8);

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

$properties = array_unique(array_merge(array_keys($data['original']), array_keys($data['changed'])));
$tableData = [];
foreach ($properties as $i => $property) {
    $tableData[] = [
        $property,
        $data['original'][$property] ?? '',
        $data['changed'][$property] ?? '',
    ];
}
$emptyValueHTML = $this->Bootstrap->node('span', ['class' => ['text-muted', 'fw-light', 'fst-italic']], __('- empty -'));

$diffTable = $this->Bootstrap->table(
    [
        'hover' => false,
        'striped' => false,
        'bordered' => false,
    ],
    [
        'items' => $tableData,
        'fields' => [
            [
                'label' => __('Property name'),
                'formatter' => function ($field, $row) {
                    return $this->Bootstrap->node('pre', [], h($field));
                }
            ],
            [
                'label' => __('Old value'),
                'formatter' => function ($field, $row) use ($randomIdOld, $emptyValueHTML) {
                    $fieldText = is_array($field) ? json_encode($field, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT) : $field;
                    $config = [
                        'text' => $fieldText,
                        'variant' => 'danger',
                        'dismissible' => false,
                        'class' => ['p-2', 'mb-0', !empty($fieldText) && is_array($field) ? "json_container_{$randomIdOld}" : ''],
                    ];
                    if (empty($fieldText)) {
                        $config['html'] = $emptyValueHTML;
                    } else {
                        $config['text'] = $fieldText;
                    }
                    return $this->Bootstrap->alert($config);
                }
            ],
            [
                'label' => __('New value'),
                'formatter' => function ($field, $row) use ($randomIdNew, $emptyValueHTML) {
                    $fieldText = is_array($field) ? json_encode($field, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT) : $field;
                    $config = [
                        'text' => $fieldText,
                        'variant' => 'success',
                        'dismissible' => false,
                        'class' => ['p-2', 'mb-0', !empty($fieldText) && is_array($field) ? "json_container_{$randomIdNew}" : ''],
                    ];
                    if (empty($fieldText)) {
                        $config['html'] = $emptyValueHTML;
                    } else {
                        $config['text'] = $fieldText;
                    }
                    return $this->Bootstrap->alert($config);
                }
            ],
        ],
    ]
);


$cards = sprintf(
    '<div class="container">
        <div class="row">
            <div class="col">%s</div>
            <div class="col">%s</div>
        </div>
    </div>',
    $this->Bootstrap->card([
        'headerText' => __('Original values'),
        'bodyHTML' => $this->element('genericElements/SingleViews/Fields/jsonField', ['field' => ['raw' => $data['original']]])
    ]),
    $this->Bootstrap->card([
        'headerText' => __('Changed values'),
        'bodyHTML' => $this->element('genericElements/SingleViews/Fields/jsonField', ['field' => ['raw' => $data['changed']]])
    ])
);

$collapse = $this->Bootstrap->collapse([
    'button' => [
        'text' => __('Show raw changes'),
        'variant' => 'link',
    ],
    'card' => false
], $cards);

echo $this->Bootstrap->modal([
    'title' => __('Acknowledge notification'),
    'size' => 'xl',
    'type' => 'confirm',
    'bodyHtml' => sprintf(
        '<div class="d-none">%s</div><p>%s</p>%s%s',
        $form,
        $changedSummary,
        $diffTable,
        $collapse
    ),
    'confirmButton' => [
        'text' => __('Acknowledge & Discard'),
        'icon' => 'check',
    ]
]);
?>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        const $containerOld = $('.json_container_<?= $randomIdOld; ?>')
        const $containerNew = $('.json_container_<?= $randomIdNew; ?>')
        if ($containerOld.length == 1) {
            $containerOld.html(syntaxHighlightJson($containerOld.text()));
        }
        if ($containerNew.length == 1) {
            $containerNew.html(syntaxHighlightJson($containerNew.text()));
        }
    });
</script>