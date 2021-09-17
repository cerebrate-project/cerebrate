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
    <span class="lock-sidebar align-self-center mt-auto w-100 d-none d-sm-block" onclick="$('.sidebar').toggleClass('expanded')">
        <a type="button" class="btn btn-sm w-100"></a>
    </span>
</div>
