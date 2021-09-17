<?php
    echo $this->Html->image('logo-purple.png', ['alt' => 'CakePHP', 'class="form-signin"']);
    echo '<div class="form-signin">';
    $template = [
        'inputContainer' => '<div class="form-floating input {{type}}{{required}}">{{content}}</div>',
        'formGroup' => '{{input}}{{label}}',
        'submitContainer' => '<div class="submit d-grid">{{content}}</div>',
    ];
    $this->Form->setTemplates($template);
    echo $this->Form->create(null, ['url' => ['controller' => 'users', 'action' => 'login']]);
    echo $this->Form->control('username', ['label' => 'Username', 'class' => 'form-control mb-2', 'placeholder' => __('Username')]);
    echo $this->Form->control('password', ['type' => 'password', 'label' => 'Password', 'class' => 'form-control mb-3', 'placeholder' => __('Password')]);
    echo $this->Form->control(__('Submit'), ['type' => 'submit', 'class' => 'btn btn-primary']);
    echo $this->Form->end();
    echo '</div>';
?>
</div>
