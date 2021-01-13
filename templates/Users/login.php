<?php
    echo $this->Html->image('logo-purple.png', ['alt' => 'CakePHP', 'class="form-signin"']);
    echo '<div class="form-signin">';
    echo $this->Form->create(null, ['url' => ['controller' => 'users', 'action' => 'login']]);
    echo $this->Form->control('username', ['label' => false, 'class' => 'form-control', 'placeholder' => __('Username')]);
    echo $this->Form->control('password', ['type' => 'password', 'label' => false, 'class' => 'form-control', 'placeholder' => __('Password')]);
    echo $this->Form->submit(__('Submit'), ['class' => 'btn btn-lg btn-primary btn-block']);
    echo $this->Form->end();
    echo '</div>';
?>
</div>
