<?php
    echo $this->element('genericElements/Form/genericForm', [
        'data' => [
            'title' => __('Add a bookmark'),
            'description' => __('Specify the name and the URL of the bookmark which will be tied to your user profile.'),
            'model' => 'UserSettings',
            'fields' => [
                [
                    'field' => 'bookmark_name',
                    'label' => __('Name'),
                    'placeholder' => __('Home page'),
                ],
                [
                    'field' => 'bookmark_label',
                    'label' => __('Label'),
                    'placeholder' => __('Home'),
                ],
                [
                    'field' => 'bookmark_url',
                    'label' => __('URL'),
                    'placeholder' => '/instance/home',
                ],
            ],
            'submit' => [
                'action' => $this->request->getParam('action')
            ]
        ],
    ]);
?>
</div>
