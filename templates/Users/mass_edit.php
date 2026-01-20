<?php
$form = $this->element('genericElements/Form/genericForm', [
    'entity' => $entity,
    'ajax' => true,
    'raw' => true,
    'data' => [
        'title' => __('Bulk Edit {0}', h(Cake\Utility\Inflector::pluralize(Cake\Utility\Inflector::humanize($this->request->getParam('controller'))))),
        'fields' => [
            [
                'type' => 'text',
                'field' => 'ids',
                'default' => !empty($id) ? json_encode([$id]) : '',
                'hidden' => true,
                'templates' => ['inputContainer' => '<div class="row mb-3 d-none">{{content}}</div>'],
            ],
            [
                'field' => 'organisation_id',
                'type' => 'dropdown',
                'label' => __('Associated organisation'),
                'options' => $dropdownData['organisation'],
                'default' => $defaultOrg ?? $loggedUser['organisation_id'],
                'requirements' => in_array('organisation_id', $validFields)
            ],
            [
                'field' => 'role_id',
                'type' => 'dropdown',
                'label' => __('Role'),
                'options' => $dropdownData['role'],
                'default' => $defaultRole ?? null,
                'requirements' => in_array('role_id', $validFields)
            ],
            [
                'field' => 'disabled',
                'type' => 'dropdown',
                'options' => [
                    true => __('User Disabled'),
                    false => __('User Enabled'),
                    'unchanged' => __('-- Unchanged --'),
                ],
                'default' => $defaultDisabledState,
                'label' => 'Disable',
            ],
        ],
        'submit' => [
            'action' => $this->request->getParam('action'),
            'hidden' => true,
        ]
    ]
]);
$formHTML = sprintf('<div class="d-block">%s</div>', $form);

if (!empty($id)) {
    $bodyMessage = __('Confirm edition of {0} #{1}?', h(Cake\Utility\Inflector::singularize($this->request->getParam('controller'))), h($id));
} else {
    $bodyMessage = __('Confirm edition of the given {0}?', h(Cake\Utility\Inflector::pluralize($this->request->getParam('controller'))));
}
$bodyHTML = sprintf('%s%s', $formHTML, $bodyMessage);

echo $bodyHTML;
