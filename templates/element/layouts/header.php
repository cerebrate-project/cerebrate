<div class="container-fluid">
    <div class="left-navbar">
        <a class="navbar-brand" href="<?= $baseurl ?>">
            <?= $this->Html->image('cerebrate-icon.svg', ['alt' => 'Cerebrate', 'height' => 50]) ?>
        </a>
    </div>
    <div class="center-navbar">
        <?= $this->element('layouts/header/header-breadcrumb'); ?>
    </div>
    <div class="right-navbar">
    <?= $this->element('layouts/header/header-right'); ?>
    </div>
</div>
