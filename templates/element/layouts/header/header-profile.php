<?php

use Cake\Routing\Router;
?>
<div class="btn-group">
    <a class="nav-link px-2 text-decoration-none profile-button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" href="#" data-bs-offset="10,20">
        <i class="<?= $this->FontAwesome->getClass('user-circle') ?> fa-lg"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-end">
        <h6 class="dropdown-header">
            <div class="fw-light"><?= __('Logged in as') ?></div>
            <div>
                <?= $this->SocialProvider->getIcon($this->request->getAttribute('identity')) ?>
                [<?= h($loggedUser['organisation']['name']) ?>] <strong><?= h($this->request->getAttribute('identity')['username']) ?></strong>
            </div>
        </h6>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="<?= Router::url(['controller' => 'users', 'action' => 'view', 'plugin' => null, h($this->request->getAttribute('identity')['id'])]) ?>">
            <i class="me-1 <?= $this->FontAwesome->getClass('user-circle') ?>"></i>
            <?= __('My Account') ?>
        </a>
        <a
            class="dropdown-item"
            href="<?= Router::url(['controller' => 'users', 'action' => 'settings', 'plugin' => null, h($this->request->getAttribute('identity')['id'])]) ?>"
        >
            <i class="me-1 <?= $this->FontAwesome->getClass('user-cog') ?>"></i>
            <?= __('Account Settings') ?>
        </a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item dropdown-item-outline-danger" href="<?= Router::url(['controller' => 'users', 'action' => 'logout', 'plugin' => null]) ?>">
            <i class="me-1 <?= $this->FontAwesome->getClass('sign-out-alt') ?>"></i>
            <?= __('Logout') ?>
        </a>
    </div>
</div>

<style>
</style>
