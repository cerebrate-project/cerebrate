<div class="d-flex">
    <div class="global-search-container d-md-block d-none">
        <span class="search-input-container">
            <input type="text" class="form-control d-inline-block" id="globalSearch" placeholder="<?= __('Search in Cerebrate...') ?>">
            <i class="icon <?= $this->FontAwesome->getClass('search') ?>"></i>
        </span>
        <button type="button" class="dropdown-toggle d-none" id="dropdownMenuSearchAll" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-reference="parent"></button>
        <div class="global-search-result-container dropdown-menu dropdown-menu-right p-0 pt-2" aria-labelledby="dropdownMenuSearchAll">
            <?= __('- No result -') ?>
        </div>
    </div>
    <div class="header-menu d-flex ml-1">
        <?= $this->element('layouts/header/header-notifications') ?>
        <?= $this->element('layouts/header/header-profile') ?>
    </div>
</div>

<style>
.top-navbar .global-search-container {
    position: relative;
}

#globalSearch {
    padding-right: 26px;
}

.top-navbar .global-search-container .search-input-container > i.icon {
    position: absolute;
    right: 8px;
    line-height: 38px;
}
</style>