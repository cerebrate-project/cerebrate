<?php
    echo $this->element('genericElements/Form/genericForm', [
        'data' => [
            'description' => __('Mailing list are email distribution lists containing individuals.'),
            'model' => 'MailingLists',
            'fields' => [
                [
                    'field' => 'name'
                ],
                [
                    'field' => 'uuid',
                    'label' => 'UUID',
                    'type' => 'uuid'
                ],
                [
                    'field' => 'releasability',
                    'type' => 'textarea'
                ],
                [
                    'field' => 'description',
                    'type' => 'textarea'
                ],
                [
                    'field' => 'active',
                    'type' => 'checkbox',
                    'default' => 1
                ],
            ],
            'submit' => [
                'action' => $this->request->getParam('action')
            ]
        ]
    ]);
?>
</div>
