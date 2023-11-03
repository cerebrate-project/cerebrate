<?php
    echo $this->element('genericElements/Form/genericForm', [
        'data' => [
            'model' => 'SharingGroups',
            'fields' => [
                [
                    'field' => 'organisation_id',
                    'type' => 'dropdown',
                    'label' => __('Owner organisation'),
                    'options' => $dropdownData['organisation']
                ],
                [
                    'field' => 'extend',
                    'type' => 'checkbox',
                    'label' => __('Can extend/administer')
                ],
            ],
            'submit' => [
                'action' => $this->request->getParam('action')
            ],
        ]
    ]);
?>
</div>
