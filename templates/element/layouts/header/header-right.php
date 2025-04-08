<div class="d-flex">
    <div class="global-search-container d-md-block d-none me-4">
        <span><a style="text-decoration: none" class="link-light" href="<?= h($baseurl) . '/users/view/' . h($loggedUser['id']) ?>"><i class="<?= $this->FontAwesome->getClass('user') ?> fa"></i> <?= h($loggedUser['username']) ?></a></span>
    </div>
    <div class="global-search-container d-md-block d-none me-4">
        <span><a style="text-decoration: none" class="link-light" href="https://cerebrate-project.org">Cerebrate</a> <a style="text-decoration: none" class="link-light" href="https://github.com/cerebrate-project/cerebrate/releases/tag/v<?= h($cerebrate_version) ?>">v<?= h($cerebrate_version) ?></a></span>
    </div>
    <div class="global-search-container d-md-block d-none">
        <span class="search-input-container">
            <input type="text" class="form-control d-inline-block" id="globalSearch" placeholder="<?= __('Search in Cerebrate...') ?>">
            <i class="icon <?= $this->FontAwesome->getClass('search') ?>"></i>
        </span>
        <div class="dropdown">
            <button type="button" class="dropdown-toggle d-none" id="dropdownMenuSearchAll" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-bs-reference="parent"></button>
            <div class="global-search-result-container dropdown-menu dropdown-menu-end p-0 pt-2" aria-labelledby="dropdownMenuSearchAll">
                <div class="search-results-wrapper text-center"><?= __('- No result -') ?></div>
            </div>
        </div>
    </div>
    <div class="header-menu d-flex ms-1">
        <?= $this->element('layouts/header/header-notifications') ?>
        <?= $this->element('layouts/header/header-profile') ?>
    </div>
</div>
