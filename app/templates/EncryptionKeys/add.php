<?php
$primaryIdentifiers = ['individual' => 'email', 'organisation' => 'name'];
echo $this->element('genericElements/Form/genericForm', array(
    'form' => $this->Form,
    'data' => array(
        'entity' => $encryptionKey,
        'title' => __('Add new encryption key for {0} #{1} ({2})', h($owner_type), h($owner_id), h($owner[$primaryIdentifiers[$owner_type]])),
        'description' => __('Alignments indicate that an individual belongs to an organisation in one way or another. The type of relationship is defined by the type field.'),
        'model' => 'Organisations',
        'fields' => array(
            array(
                'field' => 'uuid',
                'type' => 'uuid'
            ),
            array(
                'field' => 'type',
                'options' => array('pgp' => 'PGP', 'smime' => 'S/MIME')
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
