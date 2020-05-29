<?php
    $modelForForm = 'Individuals';
    echo $this->element('genericElements/Form/genericForm', array(
        'form' => $this->Form,
        'data' => array(
            'entity' => $individual,
            'title' => __('Add new individual'),
            'description' => __('Individuals are natural persons. They are meant to describe the basic information about an individual that may or may not be a user of this community. Users in genral require an individual object to identify the person behind them - however, no user account is required to store information about an individual. Individuals can have affiliations to organisations and broods as well as cryptographic keys, using which their messages can be verified and which can be used to securely contact them.'),
            'model' => 'Organisations',
            'fields' => array(
                array(
                    'field' => 'email'
                ),
                array(
                    'field' => 'first_name'
                ),
                array(
                    'field' => 'last_name'
                ),
                array(
                    'field' => 'position'
                ),
                array(
                    'field' => 'uuid',
                    'label' => 'UUID',
                    'type' => 'uuid'
                )
            ),
            'submit' => array(
                'action' => $this->request->getParam('action')
            )
        )
    ));
?>
</div>
