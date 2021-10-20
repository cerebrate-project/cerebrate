<?php
echo $this->element('genericElements/Form/genericForm', [
    'data' => [
        'description' => __('User settings form'),
        'fields' => [
            [
                'field' => 'name',
            ],
            [
                'field' => 'value'
            ],
        ],
        'submit' => [
            'action' => $this->request->getParam('action')
        ]
    ]
]);
