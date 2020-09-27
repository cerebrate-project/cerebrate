<?php
    $randomId = Cake\Utility\Security::randomString(8);
?>
<div id="accordion">
    <div class="card">
        <div class="card-header" id="heading-<?= $randomId ?>">
            <h5 class="mb0"><a href="#" class="btn btn-link" data-toggle="collapse" data-target="#view-child-<?= $randomId ?>" aria-expanded="true" aria-controls="collapseOne"><?= h($title) ?></a></h5>
        </div>
        <div class="collapse collapsed" id="view-child-<?= $randomId ?>" data-parent="#accordion" labelledby="heading-<?= $randomId ?>">
            <div id="view-child-body-<?= $randomId ?>" class="card-body" data-load-on="ready"><?= $body ?></div>
        </div>
    </div>
</div>
