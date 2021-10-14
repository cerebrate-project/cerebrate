<?php
$variationIcon = '';
$variationClass = '';
if ($variation == 0) {
    $variationIcon = 'minus';
} elseif ($variation > 0) {
    $variationIcon = 'arrow-up';
    $variationClass = 'bg-success';
} else {
    $variationIcon = 'arrow-down';
    $variationClass = 'bg-danger';
}

$variationHtml = sprintf('<div class="badge %s fw-bold"><span class="%s me-2"></span>%s</div>',
    $variationClass,
    $this->FontAwesome->getClass($variationIcon),
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
