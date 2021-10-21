<?php
use Cake\Core\Configure;
?>

<div class="form-signin panel shadow position-absolute start-50 translate-middle">
    <?php
    echo sprintf(
        '<div class="text-center mb-4">%s</div>',
        $this->Html->image('logo-purple.png', [
            'alt' => __('Cerebrate logo'),
            'width' => 100, 'height' => 100,
            'style' => ['filter: drop-shadow(4px 4px 4px #924da666);']
        ])
    );
    echo sprintf('<h4 class="text-uppercase fw-light mb-3">%s</h4>', __('Sign up'));
    $template = [
        'inputContainer' => '<div class="input {{type}}{{required}}">{{content}}</div>',
        'formGroup' => '{{label}}{{input}}',
        'submitContainer' => '<div class="submit d-grid">{{content}}</div>',
        'label' => '<label class="fw-light fs-7" {{attrs}}>{{text}}</label>'
    ];
    $this->Form->setTemplates($template);
    echo $this->Form->create(null, ['url' => ['controller' => 'users', 'action' => 'register']]);

    echo $this->Form->control('username', ['label' => __('Username'), 'class' => 'form-control mb-2']);
    echo $this->Form->control('email', ['label' => __('E-mail Address'), 'class' => 'form-control mb-3']);

    echo '<div class="row g-1 mb-3">';
        echo '<div class="col-md">';
            echo $this->Form->control('first_name', ['label' => __('First Name'), 'class' => 'form-control']);
        echo '</div>';
        echo '<div class="col-md">';
            echo $this->Form->control('last_name', ['label' => __('Last Name'), 'class' => 'form-control mb-2']);
        echo '</div>';
    echo '</div>';

    echo $this->Form->control('password', ['type' => 'password', 'label' => __('Password'), 'class' => 'form-control mb-4']);

    echo $this->Form->control(__('Sign up'), ['type' => 'submit', 'class' => 'btn btn-primary']);
    echo '<div class="text-end">';
    echo sprintf('<span class="text-secondary ms-auto" style="font-size: 0.8rem">%s <a href="/users/login" class="text-decoration-none link-primary fw-bold">%s</a></span>', __('Have an account?'), __('Sign in'));
    echo '</div>';
    echo $this->Form->end();
    ?>
</div>