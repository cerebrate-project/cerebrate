<div class="container-fluid">
    <div class="left-navbar">
        <a class="navbar-brand d-sm-block d-none" href="<?= $baseurl ?>">
            <?= $this->Html->image('cerebrate-icon.svg', ['alt' => 'Cerebrate', 'height' => 50]) ?>
        </a>
        <button class="navbar-toggler d-sm-none" type="button" data-toggle="collapse" data-target="#app-sidebar" aria-controls="app-sidebar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
    <div class="center-navbar">
        <?= $this->element('layouts/header/header-breadcrumb'); ?>
    </div>
    <div class="right-navbar">
    <?= $this->element('layouts/header/header-right'); ?>
    </div>
</div>
