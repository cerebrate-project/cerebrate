<?php
$variationIcon = '';
$variationClass = '';
if ($variation == 0) {
    $variationIcon = $this->FontAwesome->getClass('minus');
} elseif ($variation > 0) {
    $variationIcon = 'trends-arrow-up-white fs-6';
    $variationClass = 'bg-success';
} else {
    $variationIcon = 'trends-arrow-up-white fs-6 fa-rotate-180 fa-flip-vertical';
    $variationClass = 'bg-danger';
}

$variationHtml = sprintf(
    '<div class="badge %s fw-bold"><span class="%s me-2 align-middle"></span>%s</div>',
    $variationClass,
    $variationIcon,
    !empty($variation) ? h($variation) : ''
);

$titleHtml = isset($title) ? h($title) : ($titleHtml ?? '');
$leftContent = sprintf('<div class="">%s</div><h2 class="my-2">%s</h2>%s',
    $titleHtml,
    h($number ?? ''),
    $variationHtml
);
$rightContent = sprintf('<div class="">%s</div>', $this->element('charts/bar', [
    'chartData' => $chartData,
    'chartOptions' => [
        
    ]
]));

$cardContent = sprintf('<div class="highlight-panel-container d-flex align-items-center justify-content-between"><div class="number-container">%s</div><div class="chart-container w-50">%s</div></div>', $leftContent, $rightContent);

echo $this->Bootstrap->card([
    'variant' => 'secondary',
    'bodyHTML' => $cardContent,
    'bodyClass' => 'p-3',
    'class' => 'grow-on-hover shadow-sm'
]);

?>
