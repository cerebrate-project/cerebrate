<div class="sidebar-wrapper d-flex flex-column">
    <div class="sidebar-scroll">
        <div class="sidebar-content">
            <ul class="sidebar-elements">
                <?php foreach ($menu as $category => $categorized) : ?>
                    <?php if ($category == '__bookmarks') : ?>
                        <?= $this->element('layouts/sidebar/category', ['label' => __('Bookmarks'), 'class' => 'bookmark-categ']) ?>
                        <li class="bookmarks">
                            <?php foreach ($categorized as $parentName => $entry) : ?>
                                <?= $this->element('layouts/sidebar/bookmark-entry', [
                                    'entry' => $entry,
                                ])
                                ?>
                            <?php endforeach; ?>
                            <?= $this->element('layouts/sidebar/bookmark-add') ?>
                        </li>
                    <?php else : ?>
                        <?= $this->element('layouts/sidebar/category', ['label' => $category]) ?>
                        <?php foreach ($categorized as $parentName => $parent) : ?>
                            <?= $this->element('layouts/sidebar/entry', [
                                'parent' => $parent,
                            ])
                            ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <span class="lock-sidebar align-self-center mt-auto w-100 d-none d-sm-block">
        <a type="button" class="btn-lock-sidebar btn btn-sm w-100"></a>
    </span>
</div>