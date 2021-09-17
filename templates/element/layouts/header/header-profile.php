<?php
use Cake\Routing\Router;
?>
<div class="btn-group">
    <a class="nav-link px-2 text-decoration-none profile-button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" href="#" data-bs-offset="10,20">
        <i class="<?= $this->FontAwesome->getClass('user-circle') ?> fa-lg"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-end">
        <h6 class="dropdown-header"><?= h($this->request->getAttribute('identity')['username']) ?></h6>
        <a class="dropdown-item" href="<?= Router::url(['controller' => 'users', 'action' => 'view']) ?>">
            <i class="me-1 <?= $this->FontAwesome->getClass('user-circle') ?>"></i>
            <?= __('My Account') ?>
        </a>
        <a class="dropdown-item" href="<?= Router::url(['controller' => 'users', 'action' => 'userSettings']) ?>">
            <i class="me-1 <?= $this->FontAwesome->getClass('user-cog') ?>"></i>
            <?= __('Settings') ?>
        </a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="<?= Router::url(['controller' => 'users', 'action' => 'logout']) ?>">
            <i class="me-1 <?= $this->FontAwesome->getClass('sign-out-alt') ?>"></i>
            <?= __('Logout') ?>
        </a>
    </div>
</div>

<style>
</style>