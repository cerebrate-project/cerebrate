<div class="d-flex">
    <div class="global-search-container">
        <input type="text" class="form-control d-inline-block" id="globalSearch" placeholder="<?= __('Search in Cerebrate') ?>">
        <i class="icon <?= $this->FontAwesome->getClass('search') ?>"></i>
    </div>
    <div class="header-menu d-flex ml-1">
        <a class="nav-link px-2 text-decoration-none" href="#">
            <i class="<?= $this->FontAwesome->getClass('bell') ?> fa-lg"></i>
        </a>
        <a class="nav-link px-2 text-decoration-none" href="#">
            <i class="<?= $this->FontAwesome->getClass('cog') ?> fa-lg"></i>
        </a>
        <a class="nav-link px-2 text-decoration-none" href="#">
            <?= $this->element('layouts/header/header-profile') ?>
        </a>
    </div>
</div>

<style>
.top-navbar .global-search-container {
    position: relative;
}

#globalSearch {
    padding-right: 26px;
}
#globalSearch {
    padding-right: 26px;
}

.top-navbar .global-search-container > i.icon {
    position: absolute;
    right: 8px;
    line-height: 38px;
}
</style>