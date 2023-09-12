<?php
    echo $this->element('genericElements/Form/genericForm', array(
        'data' => array(
            'description' => __('Assign a user to be an administrator of the group.'),
            'model' => null,
            'fields' => array(
                array(
                    'field' => 'id',
                    'label' => __('User'),
                    'type' => 'dropdown',
                    'options' => $dropdownData['admins']
                )
            ),
            'submit' => array(
                'action' => $this->request->getParam('action')
            )
        )
    ));
?>
</div>
