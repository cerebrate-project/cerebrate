<?php
// $Parsedown = new Parsedown();
// echo $Parsedown->text($md);
?>

<h2><?= __('Home') ?></h2>
<div class="row">
    <?php foreach ($statistics as $modelName => $statistics): ?>
        <div class="col-sm-6 col-md-5 col-l-4 col-xl-3 mb-3">
            <?php
                $exploded = explode('.', $modelName);
                $modelForDisplay = $exploded[count($exploded)-1];
                $panelTitle = $this->Html->link(
                    h($modelForDisplay),
                    $this->Url->build([
                        'controller' => $modelForDisplay,
                        'action' => 'index',
                    ]),
                    ['class' => 'text-white text-decoration-none fw-light stretched-link']
                );
                echo $this->element('widgets/highlight-panel', [
                    'titleHtml' => $panelTitle,
                    'number' => $statistics['amount'],
                    'variation' => $statistics['variation'] ?? '',
                    'chartData' => $statistics['timeline'] ?? []
                ]);
            ?>
        </div>
    <?php endforeach ?>
</div>
