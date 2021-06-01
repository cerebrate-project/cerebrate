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
                    'field' => 'exposed',
                    'type' => 'checkbox'
                ],
                [
                    'field' => 'settings',
                    'type' => 'textarea'
                ],
                [
                    'field' => 'description',
                    'type' => 'textarea'
                ],
            ],
            'submit' => [
                'action' => $this->request->getParam('action')
            ],
            'url' => empty($redirect) ? null : $redirect
        ]
    ]);
?>
</div>
