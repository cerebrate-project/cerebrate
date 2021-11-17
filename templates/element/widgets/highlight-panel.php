<?php
$seed = 's-' . mt_rand();
$variationIcon = '';
$variationClass = '';
if (!empty($variation)) {
    if ($variation == 0) {
        $variationIcon = $this->FontAwesome->getClass('minus');
    } elseif ($variation > 0) {
        $variationIcon = 'trends-arrow-up-white fs-6';
        $variationClass = 'bg-success';
    } else {
        $variationIcon = 'trends-arrow-up-white fs-6 fa-rotate-180 fa-flip-vertical';
        $variationClass = 'bg-danger';
    }
}

$series = [];
$statistics_day_number = '';
if (!empty($timeline['created']['timeline'])) {
    $statistics_day_number = $timeline['created']['days'];
    $i = count($series);
    $series[$i]['name'] = __('Created');
    $series[$i]['type'] = !empty($chartType) ? $chartType : 'column';
    foreach ($timeline['created']['timeline'] as $entry) {
        $series[$i]['data'][] = ['x' => $entry['time'], 'y' => $entry['count']];
    }
}
if (!empty($timeline['modified']['timeline'])) {
    $statistics_day_number = empty($statistics_day_number) ? $timeline['modified']['days'] : $statistics_day_number;
    $i = count($series);
    $series[$i]['name'] = __('Modified');
    $series[$i]['type'] = !empty($chartType) ? $chartType : 'line';
    foreach ($timeline['modified']['timeline'] as $entry) {
        $series[$i]['data'][] = ['x' => $entry['time'], 'y' => $entry['count']];
    }
}

$variationHtml = '';
if (!empty($variation)) {
    $variationHtml = sprintf(
        '<div class="badge %s fw-bold"><span class="%s me-2 align-middle"></span>%s</div>',
        $variationClass,
        $variationIcon,
        !empty($variation) ? h($variation) : ''
    );
}

$titleHtml = isset($title) ? h($title) : ($titleHtml ?? '');
$leftContent = sprintf(
    '<div class="">%s</div><%s class="%s">%s <span class="fs-8 fw-light">%s%s</span></%s>%s',
    $titleHtml,
    (!empty($condensed) ? 'h3' : 'h2'),
    (!empty($condensed) ? 'my-1' : 'my-2'),
    h($number ?? ''),
    __('Past {0} days', $statistics_day_number),
    empty($allowConfiguration) ? '' : $this->Bootstrap->button([
        'variant' => 'link',
        'icon' => 'cog',
        'size' => 'xs',
        'nodeType' => 'a',
        'onclick' => '',
        'class' => ['btn-statistics-days-configurator-' . $seed],
        'params' => [
            'data-bs-toggle' => 'popover',
            'data-bs-title' => __('Set statistics spanning days'),
        ]
    ]),
    (!empty($condensed) ? 'h3' : 'h2'),
    $variationHtml
);
$rightContent = sprintf('<div class="">%s</div>', $this->element('charts/bar', [
    'series' => $series,
    'chartOptions' => array_merge(
        [
            'chart' => [
                'height' => '90px',
            ],
            'stroke' => [
                'width' => [0, 2],
                'curve' => 'smooth',
            ],
        ],
        !empty($chartOptions) ? $chartOptions : []
    )
]));
$cardContent = sprintf('<div class="highlight-panel-container d-flex align-items-center justify-content-between %s" style="%s %s"><div class="number-container">%s</div><div class="chart-container w-50 %s">%s</div></div>', $panelClasses ?? '', (!empty($condensed) ? 'max-height: 100px' : ''), $panelStyle ?? '', $leftContent, (!empty($condensed) ? 'p-2' : ''), $rightContent);

echo $this->Bootstrap->card([
    'variant' => 'secondary',
    'bodyHTML' => $cardContent,
    'bodyClass' => (!empty($condensed) ? 'py-1 px-2' : 'p-3'),
    'class' => ['shadow-sm', (empty($panelNoGrow) ? 'grow-on-hover' : '')]
]);

?>

<?php if (!empty($allowConfiguration)): ?>
<script>
    $(document).ready(function() {
        let popovers = new bootstrap.Popover(document.querySelector('.btn-statistics-days-configurator-<?= $seed ?>'), {
            container: 'body',
            html: true,
            sanitize: false,
            content: () => {
                return '<div class="input-group flex-nowrap"> \
                            <span class="input-group-text" id="addon-wrapping-<?= $seed ?>"><?= __('Days') ?></span> \
                            <input type="number" min="1" class="form-control" placeholder="7" aria-label="<?= __('Days') ?>" aria-describedby="addon-wrapping-<?= $seed ?>" value="<?= h($statistics_day_number) ?>"> \
                            <button class="btn btn-primary" type="button" onclick="statisticsDaysRedirect(this)"><?= __('Get statistics') ?> </button> \
                        </div>'
            }
        })
    })

    function statisticsDaysRedirect(clicked) {
        const endpoint = window.location.pathname
        const search = window.location.search
        let days = $(clicked).closest('.input-group').find('input').val()
        days = days !== undefined ? days : 7
        const searchParams = new URLSearchParams(window.location.search)
        searchParams.set('statistics_days', days);
        const url = endpoint + '?' + searchParams
        window.location = url
    }
</script>
<?php endif; ?>