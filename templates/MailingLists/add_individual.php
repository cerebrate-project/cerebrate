<?php
    echo $this->element('genericElements/Form/genericForm', [
        'data' => [
            'title' => __('Add members to mailing list {0} [{1}]', h($mailingList->name), h($mailingList->id)),
            // 'description' => __('Mailing list are email distribution lists containing individuals.'),
            'model' => 'MailingLists',
            'fields' => [
                [
                    'field' => 'individuals',
                    'type' => 'dropdown',
                    'multiple' => true,
                    'select2' => true,
                    'label' => __('Members'),
                    'class' => 'select2-input',
                    'options' => $dropdownData['individuals']
                ],
            ],
            'submit' => [
                'action' => $this->request->getParam('action')
            ],
        ],
    ]);
?>
</div>
