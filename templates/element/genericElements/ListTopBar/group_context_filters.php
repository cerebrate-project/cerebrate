<?php
    $contextArray = [];
    foreach ($data['context_filters'] as $filteringContext) {
        $filteringContext['filterCondition'] = empty($filteringContext['filterCondition']) ? [] : $filteringContext['filterCondition'];
        $urlParams = [
            'controller' => $this->request->getParam('controller'),
            'action' => 'index',
            '?' => $filteringContext['filterCondition']
        ];
        $currentQuery = $this->request->getQuery();
        unset($currentQuery['page'], $currentQuery['limit'], $currentQuery['sort']);
        if (!empty($filteringContext['filterCondition'])) { // PHP replaces `.` by `_` when fetching the request parameter
            $currentFilteringContextKey = array_key_first($filteringContext['filterCondition']);
            $currentFilteringContext = [
                str_replace('.', '_', $currentFilteringContextKey) => $filteringContext['filterCondition'][$currentFilteringContextKey]
            ];
        } else {
            $currentFilteringContext = $filteringContext['filterCondition'];
        }
        $contextArray[] = [
            'active' => $currentQuery == $currentFilteringContext,
            'isFilter' => true,
            'onClick' => 'changeIndexContext',
            'onClickParams' => [
                'this',
                $this->Url->build($urlParams),
                "#table-container-${tableRandomValue}",
                "#table-container-${tableRandomValue} table.table",
            ],
            'text' => $filteringContext['label'],
            'class' => 'btn-sm'
        ];
    }

    $dataGroup = [
        'type' => 'simple',
        'children' => $contextArray,
    ];
    if (isset($data['requirement'])) {
        $dataGroup['requirement'] = $data['requirement'];
    }
    echo '<div class="d-flex align-items-end topbar-contextual-filter">';
    echo $this->element('/genericElements/ListTopBar/group_simple', [
        'data' => $dataGroup,
        'tableRandomValue' => $tableRandomValue
    ]);
    echo '</div>';
?>

<script>
    function changeIndexContext(clicked, url, container, statusNode) {
        UI.reload(url, container, statusNode, [{
            node: clicked,
            config: {
                spinnerVariant: 'dark',
                spinnerType: 'grow',
                spinnerSmall: true
            }
        }])
    }
</script>