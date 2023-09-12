<?php
    echo $this->element('genericElements/Form/genericForm', array(
        'data' => array(
            'description' => __('Organisations can be equivalent to legal entities or specific individual teams within such entities. Their purpose is to relate individuals to their affiliations and for release control of information using the Trust Circles.'),
            'model' => 'Organisation',
            'fields' => array(
                array(
                    'field' => 'name'
                ),
                array(
                    'field' => 'uuid',
                    'label' => 'UUID',
                    'type' => 'uuid',
                    'tooltip' => __('If the Organisation already has a known UUID in another application such as MISP or another Cerebrate, please re-use this one.'),
                    'requirements' => $loggedUser['role']['perm_admin']
                ),
                array(
                    'field' => 'url'
                ),
                array(
                    'label' => __('Country'),
                    'field' => 'nationality'
                ),
                array(
                    'field' => 'sector'
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
?>
</div>
