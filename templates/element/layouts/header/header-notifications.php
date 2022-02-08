<?php

use Cake\Routing\Router;
use Cake\Utility\Hash;

$severity = [
    'primary' => -1,
    'info' => 0,
    'warning' => 1,
    'danger' => 2,
];
$maxSeverity = -1;
$notificationVariants = Hash::extract($notifications, '{n}.variant');
foreach ($notificationVariants as $notifVariant) {
    $maxSeverity = max($maxSeverity, $severity[$notifVariant] ?? 0);
}
$variant = array_flip($severity)[$maxSeverity];
?>
<div class="btn-group">
    <a class="nav-link px-2 text-decoration-none profile-button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" href="#" data-bs-offset="10,20">
        <span class="position-relative">
            <i class="<?= $this->FontAwesome->getClass('bell') ?> fa-lg"></i>
            <?=
            $this->Bootstrap->notificationBubble([
                'class' => empty($notifications) ? 'd-none' : '',
                'variant' => $variant,
            ])
            ?>
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