<?php
echo $this->element('genericElements/Form/genericForm', array(
    'form' => $this->Form,
    'data' => array(
        'entity' => $alignment,
        'title' => __('Add new alignment for {0} #{1}', \Cake\Utility\Inflector::singularize(h($scope)), h($source_id)),
        'description' => __('Alignments indicate that an individual belongs to an organisation in one way or another. The type of relationship is defined by the type field.'),
        'model' => 'Organisations',
        'fields' => array(
            array(
                'field' => ($scope === 'individuals' ? 'organisation_id' : 'individual_id'),
                'options' => ($scope === 'individuals' ? $organisations : $individuals),
                'type' => 'select'
            ),
            array(
                'field' => 'type'
            )
        ),
        'submit' => array(
            'action' => $this->request->getParam('action')
        )
    )
));
