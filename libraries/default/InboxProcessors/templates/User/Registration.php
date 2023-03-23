<?php
$infoAlert = $this->Bootstrap->alert([
    'variant' => 'info',
    'html' => sprintf('
        <ul>
            <li>%s: <strong>%s</strong></li>
            <li>%s: <strong>%s</strong></li>
        </ul>',
        __('Requested Organisation name'), $desiredOrganisation['org_name'],
        __('Requested Organisation UUID'), $desiredOrganisation['org_uuid']
    ),
    'dismissible' => false,
]);

$combinedForm = $this->element('genericElements/Form/genericForm', [
    'entity' => $userEntity,
    'ajax' => false,
    'raw' => true,
    'data' => [
        'descriptionHtml' => __('Create user account') . sprintf('<div class="mt-2">%s</div>', $infoAlert),
        'model' => 'User',
        'fields' => [
            [
                'field' => 'individual_id',
                'type' => 'dropdown',
                'label' => __('Associated individual'),
                'options' => $dropdownData['individual'],
            ],
            [
                'field' => 'username',
                'autocomplete' => 'off',
            ],
            [
                'field' => 'org_id',
                'type' => 'dropdown',
                'label' => __('Associated organisation'),
                'options' => $dropdownData['organisation'],
            ],
            [
                'field' => 'role_id',
                'type' => 'dropdown',
                'label' => __('Role'),
                'options' => $dropdownData['role'],
                'default' => $defaultRole,
            ],
            [
                'field' => 'disabled',
                'type' => 'checkbox',
                'label' => 'Disable'
            ],
            '<div class="individual-container">',
            sprintf('<div class="pb-2 fs-4">%s</div>', __('Create a new individual')),
            [
                'field' => 'email',
                'autocomplete' => 'off'
            ],
            [
                'field' => 'uuid',
                'label' => 'UUID',
                'type' => 'uuid',
                'autocomplete' => 'off'
            ],
            [
                'field' => 'first_name',
                'autocomplete' => 'off'
            ],
            [
                'field' => 'last_name',
                'autocomplete' => 'off'
            ],
            [
                'field' => 'position',
                'autocomplete' => 'off'
            ],
            '</div>',
        ],
        'submit' => [
            'action' => $this->request->getParam('action')
        ]
    ]
]);


echo $this->Bootstrap->modal([
    'title' => __('Register user'),
    'size' => 'lg',
    'type' => 'confirm',
    'bodyHtml' => sprintf(
        '<div class="form-container">%s</div>',
        $combinedForm
    ),
    'confirmButton' => [
        'text' =>  __('Create user'),
        'onclick' => 'submitRegistration',
    ],
]);
?>
</div>

<script>
    function submitRegistration(modalObject, tmpApi) {
        const $form = modalObject.$modal.find('form')
        return tmpApi.postForm($form[0]).then((result) => {
            const url = '/inbox/index'
            const $container = $('div[id^="table-container-"]')
            const randomValue = $container.attr('id').split('-')[2]
            return result
        })
    }

    $(document).ready(function() {
        $('form #individual_id-field').change(function() {
            toggleIndividualContainer($(this).val() == -1)
        })
    })

    function toggleIndividualContainer(show) {
        if (show) {
            $('div.individual-container').show()
        } else {
            $('div.individual-container').hide()
        }
    }

    function getFormData(form) {
        return Object.values(form).reduce((obj, field) => {
            if (field.type === 'checkbox') {
                obj[field.name] = field.checked;
            } else {
                obj[field.name] = field.value;
            }
            return obj
        }, {})
    }
</script>