<?php
    echo $this->element('genericElements/Form/genericForm', array(
        'data' => array(
            'description' => __('Organisations can be equivalent to legal entities or specific individual teams within such entities. Their purpose is to relate individuals to their affiliations and for release control of information using the Trust Circles.'),
            'model' => 'Organisations',
            'fields' => array(
                array(
                    'field' => 'name'
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
                    'field' => 'URL'
                ),
                array(
                    'field' => 'nationality'
                ),
                array(
                    'field' => 'sector'
                ),
                array(
                    'field' => 'type'
                )
            ),
            'metaFields' => empty($metaFields) ? [] : $metaFields,
            'submit' => array(
                'action' => $this->request->getParam('action')
            )
        )
    ));
?>
</div>
