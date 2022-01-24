<?php

use Cake\Routing\Router;
?>
<div class="btn-group">
    <a class="nav-link px-2 text-decoration-none profile-button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" href="#" data-bs-offset="10,20">
        <span class="position-relative">
            <i class="<?= $this->FontAwesome->getClass('bell') ?> fa-lg"></i>
            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-light border-2 rounded-circle">
                <span class="visually-hidden"><?= __('New notifications') ?></span>
            </span>
        </span>
    </a>
    <div class="dropdown-menu dropdown-menu-end" style="min-width: 320px; max-width: 25vw">
        <h6 class="dropdown-header d-flex justify-content-between">
            <span><?= __n('{0} Notification', '{0} Notifications', count($notifications), count($notifications)) ?></span>
        </h6>
        <?php if (empty($notifications)) : ?>
            <span class="dropdown-item-text text-nowrap user-select-none text-center">
                <?= __('- No notification -') ?>
            <span>
        <?php else : ?>
            <?php foreach ($notifications as $notification) : ?>
                <?= $this->element('layouts/header/header-notification-item', ['notification' => $notification]) ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>