<?php
    echo $this->element('genericElements/Form/genericForm', [
        'data' => [
            'title' => __('Delete a bookmark'),
            'description' => __('Specify the name and the URL of the bookmark to be deleted from your user profile.'),
            'model' => 'UserSettings',
            'fields' => [
                [
                    'field' => 'bookmark_name',
                    'label' => __('Name'),
                    'placeholder' => __('Home page'),
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
