<div class="sidebar-wrapper">
    <div class="sidebar-scroll">
        <div class="sidebar-content">
            <ul class="sidebar-elements">
                <?php foreach ($menu as $category => $categorized): ?>
                    <?= $this->element('layouts/sidebar/category', ['label' => $category]) ?>
                    <?php foreach ($categorized as $parentName => $parent): ?>
                        <?= $this->element('layouts/sidebar/entry', [
                                'parent' => $parent,
                            ])
                        ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
