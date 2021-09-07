<?php
    $seed = 'sb-' . mt_rand();
    $icon = $parent['icon'] ?? '';
    $label = $parent['label'] ?? '';
    $children = $parent['children'] ?? [];
    if ($label == 'List organisations') {
        $children = [
            [
                'label' => 'level 1',
                'children' => [
                    'Level 2' => [
                        'label' => 'level 2',
                        'children' => [
                            'Level 3' => [
                                'label' => 'level 3',
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
    if (!empty($children)) {
        $url = "#{$seed}";
    } else {
        $url = $parent['url'] ?? '#';
    }
?>

<?php if (empty($parent['skipTopMenu'])): ?>
<li class="<?= !empty($children) ? 'parent collapsed' : '' ?>">
    <a class="sidebar-link <?= !empty($children) ? 'collapsed' : '' ?>" href="<?= h($url) ?>" data-toggle="collapse">
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
<?php endif; ?>