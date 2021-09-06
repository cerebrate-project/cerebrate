<?php
// $Parsedown = new Parsedown();
// echo $Parsedown->text($md);

?>

<div class="row">
    <?php foreach ($statistics as $modelName => $statistics): ?>
        <div class="col-sm-6 col-md-4 col-l-3 col-xl-2 mb-2">
            <?php
                $modelName = explode('.', $modelName);
                $modelName = $modelName[count($modelName)-1];
                echo $this->element('widgets/highlight-panel', [
                    'title' => $modelName,
                    'number' => $statistics['amount'],
                    'variation' => $statistics['variation'] ?? '',
                    'chartData' => $statistics['timeline'] ?? []
                ]);
            ?>
        </div>
    <?php endforeach ?>
</div>