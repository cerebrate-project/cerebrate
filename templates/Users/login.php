<?php
    use Cake\Core\Configure;

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

    if (!empty(Configure::read('keycloak'))) {
        echo '<div class="form-signin pt-0">';
        echo $this->Form->create(null, [
            'url' => Cake\Routing\Router::url([
                'prefix' => false,
                'plugin' => 'ADmad/SocialAuth',
                'controller' => 'Auth',
                'action' => 'login',
                'provider' => 'keycloak',
                '?' => ['redirect' => $this->request->getQuery('redirect')]
            ]),
        ]);
        echo $this->Bootstrap->button([
            'type' => 'submit',
            'text' => __('Login with Keycloak'),
            'variant' => 'secondary',
            'class' => ['d-block', 'w-100'],
            'image' => [
                'path' => '/img/keycloak_logo.png',
                'alt' => 'Keycloak'
            ]
        ]);
        echo $this->Form->end();
        echo '</div>';
    }
?>
</div>
