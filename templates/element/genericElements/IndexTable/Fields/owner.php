<?php
    $type = $this->Hash->extract($row, $field['owner_type_path'])[0];
    $owner = $row[$type];
    $types = [
        'individual' => [
            'uri' => 'individuals',
            'name' => __('Individual'),
            'identityField' => 'email'
        ],
        'organisation' => [
            'uri' => 'organisations',
            'name' => __('Organisation'),
            'identityField' => 'name'
        ]
    ];
    echo sprintf(
        '<span class="font-weight-bold">%s</span>: %s',
        $types[$type]['name'],
        $this->Html->link(
            sprintf(
                '(%s) %s',
                h($owner['id']),
                h($owner[$types[$type]['identityField']])
            ),
            ['controller' => $types[$type]['uri'], 'action' => 'view', $owner['id']],
            [
                'class' => 'link-unstyled'
            ]
        )
    );

?>
