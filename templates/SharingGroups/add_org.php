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
            ],
            'submit' => [
                'action' => $this->request->getParam('action')
            ],
        ]
    ]);
?>
</div>
