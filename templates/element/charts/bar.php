<?php

$chartOptions = $chartOptions ?? [];
$chartData = $chartData ?? [];
$seed = mt_rand();
$chartId = "chart-{$seed}";

// Transform the chart data into the expected format
$data = [];
foreach ($chartData as $i => $entry) {
    $data[] = $entry['count'];
}
?>

<div id="<?= $chartId ?>"></div>


<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
(function() {
    const passedOptions = <?= json_encode($chartOptions) ?>;
    const defaultOptions = {
        chart: {
            id: '<?= $chartId ?>',
            type: 'bar',
            sparkline: {
                enabled: true
            },
            dropShadow: {
                enabled: true,
                top: 1,
                left: 1,
                blur: 2,
                opacity: 0.2,
            },
            animations: {
                enabled: false
            },
        },
        series: [{
            data: <?= json_encode($data) ?>,
        }],
        colors: ['var(--success)'],
        tooltip: {
            x: {
                show: false
            },
            y: {
                title: {
                    formatter: function formatter(val) {
                    return '';
                    }
                }
            },
            theme: '<?= !empty($darkMode) ? 'dark' : 'light' ?>'
        },
    }
    const chartOptions = Object.assign({}, defaultOptions, passedOptions)
    new ApexCharts(document.querySelector('#<?= $chartId ?>'), chartOptions).render();

})()
</script>

<style>
    .apexcharts-tooltip.apexcharts-theme-light {
        color: black !important
    }
</style>