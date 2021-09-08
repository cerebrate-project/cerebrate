<?php
    $seed = 'sb-' . mt_rand();
    $icon = $parent['icon'] ?? '';
    $label = $parent['label'] ?? '';
    $children = $parent['children'] ?? [];
    if (!empty($children)) {
        $url = "#{$seed}";
    } else {
        $url = $parent['url'] ?? '#';
    }
?>

<li class="<?= !empty($children) ? 'parent collapsed' : '' ?>">
    <a class="sidebar-link <?= !empty($children) ? 'collapsed' : '' ?>" href="<?= h($url) ?>" <?= !empty($children) ? 'data-toggle="collapse"' : '' ?>>
        <i class="sidebar-icon <?= $this->FontAwesome->getClass($icon) ?>"></i>
        <span class="text"><?= h($label) ?></span>
    </a>
    <?php if (!empty($children)): ?>
        <?= $this->element('layouts/sidebar/sub-menu', [
                'seed' => $seed,
                'children' => $children,
            ]);
        ?>
    <?php endif; ?>
</li>