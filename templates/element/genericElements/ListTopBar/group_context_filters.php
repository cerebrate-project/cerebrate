<?php
    $contextArray = [];
    foreach ($filteringContexts as $filteringContext) {
        $filteringContext['filterCondition'] = empty($filteringContext['filterCondition']) ? [] : $filteringContext['filterCondition'];
        $urlParams = [
            'controller' => $this->request->getParam('controller'),
            'action' => 'index',
            '?' => $filteringContext['filterCondition']
        ];
        $currentQuery = $this->request->getQuery();
        unset($currentQuery['page'], $currentQuery['limit'], $currentQuery['sort']);
        $contextArray[] = [
            'active' => $currentQuery == $filteringContext['filterCondition'],
            'url' => $this->Url->build($urlParams),
            'text' => $filteringContext['label'],
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