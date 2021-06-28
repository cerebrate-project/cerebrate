<?php
$this->extend('LocalTool/GenericRequest');
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
echo sprintf('<div class="d-none">%s</div><div class="form-error-container"></div>', $form);