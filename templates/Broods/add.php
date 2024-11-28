<?php
    echo $this->element('genericElements/Form/genericForm', array(
        'data' => array(
            'description' => __('Cerebrate can connect to other Cerebrate instances to exchange trust information and to instrument interconnectivity between connected local tools. Each such Cerebrate instance with its connected tools is considered to be a brood.'),
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
                    'type' => 'dropdown',
                    'empty' => __('-- pick one --')
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
