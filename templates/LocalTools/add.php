<?php
    echo $this->element('genericElements/Form/genericForm', [
        'data' => [
            'description' => __('Add connections to local tools via any of the available connectors below.'),
            'model' => 'LocalTools',
            'fields' => [
                [
                    'field' => 'name'
                ],
                [
                    'field' => 'connector',
                    'options' => $dropdownData['connectors'],
                    'type' => 'dropdown'
                ],
                [
                    'field' => 'settings',
                    'type' => 'textarea'
                ]
            ],
            'submit' => [
                'action' => $this->request->getParam('action')
            ]
        ]
    ]);
?>
</div>
