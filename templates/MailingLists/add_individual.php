<?php
echo $this->element('genericElements/Form/genericForm', [
    'data' => [
        'title' => __('Add members to `{0}` [{1}]', h($mailingList->name), h($mailingList->id)),
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
            [
                'field' => 'chosen_emails',
                'type' => 'text',
                'templates' => ['inputContainer' => '<div class="row mb-3 d-none">{{content}}</div>'],
            ],
            '<div class="alternate-emails-container panel d-none"></div>'
        ],
        'submit' => [
            'action' => $this->request->getParam('action')
        ],
    ],
]);
?>
</div>

<script>
    (function() {
        let individuals = {}
        $('#individuals-field').on('select2:select select2:unselect', function(e) {
            const selected = e.params.data;
            fetchIndividual(selected.id).then(() => {
                udpateAvailableEmails($(e.target).select2('data'));
            })
        });

        function udpateAvailableEmails(selected) {
            const $container = $('.alternate-emails-container')
            $container.empty()
            $container.toggleClass('d-none', selected.length == 0)
            selected.forEach(selectData => {
                const individual = individuals[selectData.id]
                let formContainers = [genForContainer(`primary-${individual.id}`, individual.email, '<?= __('primary email') ?>', true, true)]
                if (individual.alternate_emails !== undefined) {
                    individual.alternate_emails.forEach(alternateEmail => {
                        formContainers.push(
                            genForContainer(alternateEmail.id, alternateEmail.value, `${alternateEmail.metaTemplate.namespace} :: ${alternateEmail.metaTemplate.name}`, false)
                        )
                    })
                }
                const $individualFullName = $('<div/>').addClass('fw-light fs-5 mt-2').text(individual.full_name)
                const $individualContainer = $('<div/>').addClass('individual-container').data('individualid', individual.id)
                    .append($individualFullName).append(formContainers)
                $container.append($individualContainer)
                registerChangeListener()
                injectSelectedEmailsIntoForm()
            });
        }

        function genForContainer(id, email, email_source, is_primary = true, checked = false) {
            const $formContainer = $('<div/>').addClass('form-check ms-2')
            $formContainer.append(
                $('<input/>').addClass('form-check-input').attr('type', 'checkbox').attr('id', `individual-${id}`)
                .attr('value', is_primary ? 'primary' : id).prop('checked', checked),
                $('<label/>').addClass('form-check-label').attr('for', `individual-${id}`).append(
                    $('<span/>').text(email),
                    $('<span/>').addClass('fw-light fs-8 align-middle ms-2').text(`${email_source}`)
                )
            )
            return $formContainer
        }

        function registerChangeListener() {
            $('.alternate-emails-container .individual-container input')
                .off('change.udpate')
                .on('change.udpate', injectSelectedEmailsIntoForm)
        }

        function injectSelectedEmailsIntoForm() {
            const selectedEmails = getSelectedEmails()
            $('#chosen_emails-field').val(JSON.stringify(selectedEmails))
        }

        function getSelectedEmails() {
            selectedEmails = {}
            $('.alternate-emails-container .individual-container').each(function() {
                const $individualContainer = $(this)
                const individualId = $individualContainer.data('individualid')
                selectedEmails[individualId] = []
                const $inputs = $individualContainer.find('input:checked').each(function() {
                    selectedEmails[individualId].push($(this).val())
                })
            })
            return selectedEmails
        }

        function fetchIndividual(id) {
            const urlGet = `/individuals/view/${id}`
            const options = {
                statusNode: $('.alternate-emails-container')
            }
            if (individuals[id] !== undefined) {
                return Promise.resolve(individuals[id])
            }
            return AJAXApi.quickFetchJSON(urlGet, options)
                .then(individual => {
                    individuals[individual.id] = individual
                })
                .catch((e) => {
                    UI.toast({
                        variant: 'danger',
                        text: '<?= __('Could not fetch individual') ?>'
                    })
                })
        }
    })()
</script>