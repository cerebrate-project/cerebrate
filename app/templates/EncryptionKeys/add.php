<?php
$primaryIdentifiers = ['individual' => 'email', 'organisation' => 'name'];
echo $this->element('genericElements/Form/genericForm', array(
    'form' => $this->Form,
    'data' => array(
        'entity' => $encryptionKey,
        'title' => __('Add new encryption key'),
        'description' => __('Alignments indicate that an individual belongs to an organisation in one way or another. The type of relationship is defined by the type field.'),
        'model' => 'Organisations',
        'fields' => array(
            array(
                'field' => 'owner_type',
                'label' => __('Owner type'),
                'options' => array_combine(array_keys($dropdownData), array_keys($dropdownData)),
                'type' => 'dropdown'
            ),
            array(
                'field' => 'organisation_id',
                'label' => __('Owner organisation'),
                'options' => $dropdownData['organisation'],
                'type' => 'dropdown',
                'stateDependence' => [
                    'source' => '#owner_type-field',
                    'option' => 'organisation'
                ]
            ),
            array(
                'field' => 'individual_id',
                'label' => __('Owner individual'),
                'options' => $dropdownData['individual'],
                'type' => 'dropdown',
                'stateDependence' => [
                    'source' => '#owner_type-field',
                    'option' => 'individual'
                ]
            ),
            array(
                'field' => 'uuid',
                'type' => 'uuid'
            ),
            array(
                'field' => 'type',
                'options' => array('pgp' => 'PGP', 'smime' => 'S/MIME'),
                'type' => 'dropdown'
            ),
            array(
                'field' => 'encryption_key',
                'label' => __('Public key'),
                'type' => 'textarea',
                'rows' => 8
            )
        ),
        'submit' => array(
            'action' => $this->request->getParam('action')
        )
    )
));
