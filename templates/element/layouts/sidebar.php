<div class="sidebar-wrapper d-flex flex-column">
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
    <span class="lock-sidebar align-self-center mt-auto w-100" onclick="$('.sidebar').toggleClass('expanded')">
        <a type="button" class="btn btn-<?= empty($darkMode) ? 'light' : 'dark' ?> btn-sm w-100">
            <!-- <i class="<?= $this->FontAwesome->getClass('angle-double-right') ?>"></i> -->
        </a>
    </span>
</div>
