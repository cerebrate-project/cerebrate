<?php
    echo $this->element('genericElements/Form/genericForm', array(
        'data' => array(
            'description' => __('Individuals are natural persons. They are meant to describe the basic information about an individual that may or may not be a user of this community. Users in genral require an individual object to identify the person behind them - however, no user account is required to store information about an individual. Individuals can have affiliations to organisations and broods as well as cryptographic keys, using which their messages can be verified and which can be used to securely contact them.'),
            'model' => 'Organisations',
            'fields' => array(
                array(
                    'field' => 'email'
                ),
                array(
                    'field' => 'uuid',
                    'label' => 'UUID',
                    'type' => 'uuid'
                ),
                array(
                    'field' => 'first_name'
                ),
                array(
                    'field' => 'last_name'
                ),
                array(
                    'field' => 'position'
                )
            ),
            'metaFields' => empty($metaFields) ? [] : $metaFields,
            'submit' => array(
                'action' => $this->request->getParam('action')
            )
        )
    ));
?>
</div>
