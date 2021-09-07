<?php
// $Parsedown = new Parsedown();
// echo $Parsedown->text($md);
?>

<h2><?= __('Home') ?></h2>
<div class="row">
    <?php foreach ($statistics as $modelName => $statistics): ?>
        <div class="col-sm-6 col-md-4 col-l-3 col-xl-2 mb-2">
            <?php
                $exploded = explode('.', $modelName);
                $modelForDisplay = $exploded[count($exploded)-1];
                $panelTitle = $this->Html->link(
                    h($modelForDisplay),
                    $this->Url->build([
                        'controller' => $modelForDisplay,
                        'action' => 'index',
                    ]),
                    ['class' => 'text-white']
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