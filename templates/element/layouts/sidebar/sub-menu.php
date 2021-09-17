<?php
    $seed = $seed ?? 'sd-' . mt_rand();
?>

<ul id="<?= $seed ?>" class="sub-menu collapse <?= !empty($open) ? 'show' : '' ?>">
    <?php foreach ($children as $child): ?>
        <?= $this->element('layouts/sidebar/entry', [
                'parent' => $child,
            ])
        ?>
    <?php endforeach; ?>
</ul>
