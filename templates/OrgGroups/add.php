<?php
    echo $this->element('genericElements/Form/genericForm', array(
        'data' => array(
            'description' => __('OrgGroups are an administrative concept, multiple organisations can belong to a grouping that allows common management by so called "GroupAdmins". This helps grouping organisations by sector, country or other commonalities into co-managed sub-communities.'),
            'model' => 'OrgGroup',
            'fields' => array(
                array(
                    'field' => 'name'
                ),
                array(
                    'field' => 'uuid',
                    'label' => 'UUID',
                    'type' => 'uuid'
                ),
                array(
                    'field' => 'description'
                )
            ),
            'submit' => array(
                'action' => $this->request->getParam('action')
            )
        )
    ));
?>
</div>
