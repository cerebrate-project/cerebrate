<?php

$statisticsHtml = '';
$panelOptions = [
    'condensed' => true,
    'panelNoGrow' => true,
    'allowConfiguration' => true,
    'chartType' => 'line',
    'chartOptions' => [
        'chart' => [
            'height' => '60px',
        ],
        'stroke' => [
            'width' => 2,
            'curve' => 'smooth',
        ],
    ]
];

if (!empty($statistics['created'])) {
    $statisticsHtml .= $this->element('genericElements/IndexTable/Statistics/index_statistic_timestamp', [
        'timeline' => $statistics,
    ]);
}
if (!empty($statistics['usage'])) {
    $statisticsHtml .= $this->element('genericElements/IndexTable/Statistics/index_statistic_field_amount', [
        'statistics' => $statistics,
    ]);
}
$statisticsHtml = sprintf('<div class="container-fluid"><div class="row gx-2">%s</div></div>', $statisticsHtml);
echo sprintf('<div class="index-statistic-container">%s</div>', $statisticsHtml);
?>
