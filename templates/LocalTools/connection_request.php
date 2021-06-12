<?php
    $dropdown = [];
    foreach ($data['local_tools'] as $local_tool) {
        $dropdown[$local_tool['id']] = $local_tool['name'];
    }
    echo $this->element('genericElements/Form/genericForm', [
        'data' => [
            'description' => __(
                'Connect the remote tool ({0}) on remote brood ({1}) using the local tool selected below.',
                h($data['remoteTool']['name']),
                h($data['remoteCerebrate']['name'])
            ),
            'model' => 'LocalTools',
            'fields' => [
                [
                    'field' => 'local_tool_id',
                    'options' => $dropdown,
                    'type' => 'dropdown'
                ]
            ],
            'submit' => [
                'action' => $this->request->getParam('action')
            ]
        ]
    ]);
?>
</div>
