<?php
    echo $this->element('genericElements/Form/genericForm', array(
        'data' => array(
            'description' => __('Assign an organisation to the group.'),
            'model' => null,
            'fields' => array(
                array(
                    'field' => 'id',
                    'label' => __('Organisation'),
                    'type' => 'dropdown',
                    'options' => $dropdownData['orgs']
                )
            ),
            'submit' => array(
                'action' => $this->request->getParam('action')
            )
        )
    ));
?>
</div>
