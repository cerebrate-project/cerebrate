<?php

use Cake\Routing\Router;

$seed = 's-' . mt_rand();
$datetimeMW = 90;
$variant = empty($notification['variant']) ? 'primary' : $notification['variant'];
?>
<a
    class="dropdown-item px-2"
    href="<?= Router::url($notification['router']) ?>"
    style="max-height: 76px;"
    title="<?= sprintf('%s:&#010; %s', $this->ValueGetter->get($notification['text']), $this->ValueGetter->get($notification['details'])) ?>"
>
    <div class="d-flex align-items-center">
        <?php if (!empty($notification['icon'])) : ?>
            <span class="rounded-circle <?= "btn-{$variant} me-2" ?> position-relative" style="width: 36px; height: 36px">
                <?= $this->Bootstrap->icon($notification['icon'], ['class' => ['fa-fw', 'position-absolute top-50 start-50 translate-middle']]) ?>
            </span>
        <?php endif; ?>
        <span class="" style="max-width: calc(100% - 40px - 0.25rem);">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-truncate" style=" max-width: calc(100% - <?= $datetimeMW ?>px);"><?= $this->ValueGetter->get($notification['text']) ?></span>
                <?php if (!empty($notification['datetime'])) : ?>
                    <small id="<?= $seed ?>" class="text-muted fw-light" style="max-width: <?= $datetimeMW ?>px;"><?= h($notification['datetime']->format('Y-m-d\TH:i:s')) ?></small>
                <?php endif; ?>
            </div>
            <?php if (!empty($notification['details'])) : ?>
                <small class="text-muted text-wrap lh-1 text-truncate" style="-webkit-line-clamp: 2; -webkit-box-orient: vertical; display: -webkit-box;">
                    <?= $this->ValueGetter->get($notification['details']) ?>
                </small>
            <?php endif; ?>
        </span>
    </div>
</a>
<script>
    document.getElementById('<?= $seed ?>').innerHTML = moment(document.getElementById('<?= $seed ?>').innerHTML).fromNow();
</script>