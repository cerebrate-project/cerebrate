<?php
    echo $this->element('genericElements/Form/genericForm', array(
        'data' => array(
            'description' => __('Sharing groups are distribution lists usable by tools that can exchange information with a list of trusted partners. Create recurring or ad hoc sharing groups and share them with the members of the sharing group.'),
            'model' => 'Organisations',
            'fields' => array(
                array(
                    'field' => 'name'
                ),
                [
                    'field' => 'organisation_id',
                    'type' => 'dropdown',
                    'label' => __('Owner organisation'),
                    'options' => $dropdownData['organisation']
                ],
                array(
                    'field' => 'releasability',
                    'type' => 'textarea'
                ),
                array(
                    'field' => 'description',
                    'type' => 'textarea'
                ),
                array(
                    'field' => 'uuid',
                    'label' => 'UUID',
                    'type' => 'uuid'
                ),
                array(
                    'field' => 'active',
                    'type' => 'checkbox',
                    'default' => 1
                )
            ),
            'submit' => array(
                'action' => $this->request->getParam('action')
            )
        )
    ));
?>
</div>
