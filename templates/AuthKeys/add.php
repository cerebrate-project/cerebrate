<?php
echo $this->element('genericElements/Form/genericForm', array(
    'data' => array(
        'description' => __('Authkeys are used for API access. A user can have more than one authkey, so if you would like to use separate keys per tool that queries Cerebrate, add additional keys. Use the comment field to make identifying your keys easier.'),
        'fields' => array(
            array(
                'field' => 'user_id',
                'label' => __('User'),
                'options' => $dropdownData['user'],
                'type' => 'dropdown'
            ),
            array(
                'field' => 'comment'
            ),
            array(
                'field' => 'expiration',
                'label' => 'Expiration'
            )
        ),
        'submit' => array(
            'action' => $this->request->getParam('action')
        )
    )
));
