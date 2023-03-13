<?php

use Cake\Core\Configure;

$passwordRequired = false;
$showPasswordField = false;
if ($this->request->getParam('action') === 'add') {
    $dropdownData['individual'] = ['new' => __('New individual')] + $dropdownData['individual'];
    if (!Configure::check('password_auth.enabled') || Configure::read('password_auth.enabled')) {
        $passwordRequired = 'required';
    }
}
if (!Configure::check('password_auth.enabled') || Configure::read('password_auth.enabled')) {
    $showPasswordField = true;
}
echo $this->element('genericElements/Form/genericForm', [
    'data' => [
        'description' => __('Roles define global rules for a set of users, including first and foremost access controls to certain functionalities.'),
        'model' => 'Roles',
        'fields' => [
            [
                'field' => 'individual_id',
                'type' => 'dropdown',
                'label' => __('Associated individual'),
                'options' => isset($dropdownData['individual']) ? $dropdownData['individual'] : [],
                'conditions' => $this->request->getParam('action') === 'add'
            ],
            [
                'field' => 'individual.email',
                'stateDependence' => [
                    'source' => '#individual_id-field',
                    'option' => 'new'
                ],
                'required' => false
            ],
            [
                'field' => 'individual.first_name',
                'label' => 'First name',
                'stateDependence' => [
                    'source' => '#individual_id-field',
                    'option' => 'new'
                ],
                'required' => false
            ],
            [
                'field' => 'individual.last_name',
                'label' => 'Last name',
                'stateDependence' => [
                    'source' => '#individual_id-field',
                    'option' => 'new'
                ],
                'required' => false
            ],
            [
                'field' => 'username',
                'autocomplete' => 'off'
            ],
            [
                'field' => 'organisation_id',
                'type' => 'dropdown',
                'label' => __('Associated organisation'),
                'options' => $dropdownData['organisation'],
                'default' => $loggedUser['organisation_id']
            ],
            [
                'field' => 'password',
                'label' => __('Password'),
                'type' => 'password',
                'required' => $passwordRequired,
                'autocomplete' => 'new-password',
                'value' => '',
                'requirements' => $showPasswordField,
            ],
            [
                'field' => 'confirm_password',
                'label' => __('Confirm Password'),
                'type' => 'password',
                'required' => $passwordRequired,
                'autocomplete' => 'off',
                'requirements' => $showPasswordField,
            ],
            [
                'field' => 'role_id',
                'type' => 'dropdown',
                'label' => __('Role'),
                'options' => $dropdownData['role'],
                'default' => $defaultRole ?? null
            ],
            [
                'field' => 'disabled',
                'type' => 'checkbox',
                'label' => 'Disable'
            ],
        ],
        'submit' => [
            'action' => $this->request->getParam('action')
        ]
    ]
]);
?>

<script>
    $(document).ready(function() {
        const entity = <?= json_encode($entity) ?>;
        console.log(entity);
        if (entity.MetaTemplates) {
            for (const [metaTemplateId, metaTemplate] of Object.entries(entity.MetaTemplates)) {
                for (const [metaTemplateFieldId, metaTemplateField] of Object.entries(metaTemplate.meta_template_fields)) {
                    let metaFieldId = false
                    if (metaTemplateField.metaFields !== undefined && Object.keys(metaTemplateField.metaFields).length > 0) {
                        metaFieldId = Object.keys(metaTemplateField.metaFields)[0]
                    }
                    let metafieldInput
                    const baseQueryPath = `MetaTemplates.${metaTemplateId}.meta_template_fields.${metaTemplateFieldId}.metaFields`
                    if (metaFieldId) {
                        metafieldInput = document.getElementById(`${baseQueryPath}.${metaFieldId}.value-field`)
                    } else {
                        metafieldInput = document.getElementById(`${baseQueryPath}.new.0-field`)
                    }
                    if (metafieldInput !== null) {
                        const permissionWarnings = buildPermissionElement(metaTemplateField)
                        $(metafieldInput.parentElement).append(permissionWarnings)
                    }
                }
            }
        }

        function buildPermissionElement(metaTemplateField) {
            const warningTypes = ['danger', 'warning', 'info', ]
            const $span = $('<span>').addClass('ms-2')
            warningTypes.forEach(warningType => {
                if (metaTemplateField[warningType]) {
                    $theWarning = $('<span>')
                        .addClass([
                            `text-${warningType}`,
                            'ms-1',
                        ])
                        .append($(metaTemplateField[warningType]))
                    $span.append($theWarning)
                }
            });
            return $span
        }
    })
</script>