<?php
    use Cake\Core\Configure;

    echo '<div class="form-signin panel shadow position-absolute start-50 translate-middle">';
        echo sprintf(
            '<div class="text-center mb-4">%s</div>',
            $this->Html->image('logo-purple.png', [
                'alt' => __('Cerebrate logo'),
                'width' => 100, 'height' => 100,
                'style' => ['filter: drop-shadow(4px 4px 4px #924da666);']
            ])
        );
        echo sprintf('<h4 class="text-uppercase fw-light mb-3">%s</h4>', __('Sign in'));
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

        if (!empty(Configure::read('keycloak'))) {
            echo sprintf('<div class="d-flex align-items-center my-3"><hr class="d-inline-block flex-grow-1"/><span class="mx-3 fw-light">%s</span><hr class="d-inline-block flex-grow-1"/></div>', __('Or'));
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
        }
    echo '</div>';

?>
</div>
