<?php
use Cake\Routing\Router;
?>
<div class="btn-group">
    <a class="nav-link px-2 text-decoration-none profile-button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" href="#" data-offset="10,20">
        <i class="<?= $this->FontAwesome->getClass('bell') ?> fa-lg"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-right">
        <?php if (empty($notifications)): ?>
            <h6 class="dropdown-header"><?= __('- No notification -') ?></h6>
        <?php endif; ?>
    </div>
</div>
