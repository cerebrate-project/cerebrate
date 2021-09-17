<?php
    $seed = 'sb-' . mt_rand();
    $icon = $parent['icon'] ?? '';
    $label = $parent['label'] ?? '';
    $children = $parent['children'] ?? [];
    $active = false;

    if (!empty($children)) {
        $url = "#{$seed}";
    } else {
        $url = $parent['url'] ?? '#';
    }

    $controller = \Cake\Utility\Inflector::variable($this->request->getParam('controller'));
    $action = \Cake\Utility\Inflector::variable($this->request->getParam('action'));
    $currentURL = "/{$controller}/{$action}";
    if ($url == $currentURL) {
        $active = true;
    }

    $hasActiveChild = false;
    if (!empty($children)) {
        $flattened = Cake\Utility\Hash::flatten($children);
        $flattenedValues = array_values($flattened);
        if (in_array($currentURL, $flattenedValues)) {
            $hasActiveChild = true;
        }
    }
?>

<li class="<?= !empty($children) ? 'parent collapsed' : '' ?>">
    <a class="sidebar-link <?= !empty($children) ? 'collapsed' : '' ?> <?= $active ? 'active' : '' ?> <?= $hasActiveChild ? 'have-active-child' : '' ?>" href="<?= h($url) ?>" <?= !empty($children) ? 'data-bs-toggle="collapse"' : '' ?> <?= $hasActiveChild ? 'aria-expanded="true"' : '' ?>>
        <i class="sidebar-icon <?= $this->FontAwesome->getClass($icon) ?>"></i>
        <span class="text"><?= h($label) ?></span>
    </a>
    <?php if (!empty($children)): ?>
        <?= $this->element('layouts/sidebar/sub-menu', [
                'seed' => $seed,
                'children' => $children,
                'open' => $hasActiveChild
            ]);
        ?>
    <?php endif; ?>
</li>
