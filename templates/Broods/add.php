<?php
    $modelForForm = 'Individuals';
    echo $this->element('genericElements/Form/genericForm', array(
        'data' => array(
            'description' => __('Individuals are natural persons. They are meant to describe the basic information about an individual that may or may not be a user of this community. Users in genral require an individual object to identify the person behind them - however, no user account is required to store information about an individual. Individuals can have affiliations to organisations and broods as well as cryptographic keys, using which their messages can be verified and which can be used to securely contact them.'),
            'model' => 'Organisations',
            'fields' => array(
                array(
                    'field' => 'name'
                ),
                array(
                    'field' => 'url',
                    'label' => __('URL')
                ),
                array(
                    'field' => 'authkey',
                    'label' => 'Authkey',
                    'type' => 'authkey',
                    'default' => ''
                ),
                array(
                    'field' => 'description',
                    'type' => 'textarea'
                ),
                array(
                    'field' => 'organisation_id',
                    'label' => __('Owner organisation'),
                    'options' => $dropdownData['organisation'],
                    'type' => 'dropdown'
                ),
                array(
                    'field' => 'trusted',
                    'label' => __('Trusted upstream source'),
                    'type' => 'checkbox'
                ),
                array(
                    'field' => 'pull',
                    'label' => __('Enable pulling of trust information'),
                    'type' => 'checkbox'
                ),
                array(
                    'field' => 'skip_proxy',
                    'label' => 'Skip proxy',
                    'type' => 'checkbox'
                )
            ),
            'submit' => array(
                'action' => $this->request->getParam('action')
            )
        )
    ));
?>
</div>
