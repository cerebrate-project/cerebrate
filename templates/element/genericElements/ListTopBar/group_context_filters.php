<?php
    $contextArray = [];
    $currentContext = !empty($currentContext) ? $currentContext : '_all';
    foreach ($contexts as $context) {
        $urlParams = [
            'controller' => $this->request->getParam('controller'),
            'action' => 'index',
        ];
        if ($context != '_all') {
            $urlParams['?'] = ['scope' => $context];
        }
        $contextArray[] = [
            'active' => $context === $currentContext,
            'url' => $this->Url->build($urlParams),
            'text' => Cake\Utility\Inflector::humanize($context),
        ];
    }

    $dataGroup = [
        'type' => 'simple',
        'children' => $contextArray,
    ];
    if (isset($data['requirement'])) {
        $dataGroup['requirement'] = $data['requirement'];
    }
    echo $this->element('/genericElements/ListTopBar/group_simple', [
        'data' => $dataGroup
    ]);