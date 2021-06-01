<?php
    $formUser = $this->element('genericElements/Form/genericForm', [
        'entity' => $userEntity,
        'ajax' => false,
        'raw' => true,
        'data' => [
            'description' => __('Create user account'),
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
                    'field' => 'role_id',
                    'type' => 'dropdown',
                    'label' => __('Role'),
                    'options' => $dropdownData['role']
                ],
                [
                    'field' => 'disabled',
                    'type' => 'checkbox',
                    'label' => 'Disable'
                ]
            ],
            'submit' => [
                'action' => $this->request->getParam('action')
            ]
        ]
    ]);

    $formIndividual = $this->element('genericElements/Form/genericForm', [
        'entity' => $individualEntity,
        'ajax' => false,
        'raw' => true,
        'data' => [
            'description' => __('Create individual'),
            'model' => 'Individual',
            'fields' => [
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
        'bodyHtml' => sprintf('<div class="user-container">%s</div><div class="individual-container">%s</div>',
            $formUser,
            $formIndividual
        ),
        'confirmText' => __('Create user'),
        'confirmFunction' => 'submitRegistration'
    ]);
?>
</div>

<script>
    function submitRegistration(modalObject, tmpApi) {
        const $forms = modalObject.$modal.find('form')
        const url = $forms[0].action
        const data1 = getFormData($forms[0])
        const data2 = getFormData($forms[1])
        const data = {...data1, ...data2}
        return tmpApi.postData(url, data)
    }

    $(document).ready(function() {
        $('div.user-container #individual_id-field').change(function() {
            if ($(this).val() == -1) {
                $('div.individual-container').show()
            } else {
                $('div.individual-container').hide()
            }
        })
    })

    function getFormData(form) {
        return Object.values(form).reduce((obj,field) => {
            if (field.type === 'checkbox') {
                obj[field.name] = field.checked;
            } else {
                obj[field.name] = field.value;
            }
            return obj
        }, {})
    }
</script>

<style>
div.individual-container > div, div.user-container > div {
    font-size: 1.5rem;
}
</style>