<?php
    $menu = array(
        'home' => array(
            'url' => '#',
            'class' => 'navbar-brand',
            'text' => 'Cerebrate'
        ),
        'collapse' => array(
            array(
                'type' => 'group',
                'name' => 'ContactDB',
                'children' => array(
                    array(
                        'text' => __('List Organisations'),
                        'url' => '/organisations/index'
                    ),
                    array(
                        'text' => __('Add Organisation'),
                        'url' => '/organisations/add'
                    ),
                    array(
                        'type' => 'divider'
                    ),
                    array(
                        'text' => __('List Individuals'),
                        'url' => '/individuals/index'
                    ),
                    array(
                        'text' => __('Add Individual'),
                        'url' => '/individuals/add'
                    ),
                )
            ),
            array(
                'type' => 'group',
                'name' => 'Trust Circles',
                'children' => array(
                    array(
                        'text' => __('List Sharing Groups'),
                        'url' => '/sharing_groups/index'
                    ),
                    array(
                        'text' => __('Add Sharing Group'),
                        'url' => '/sharing_groups/add'
                    )
                )
            ),
            array(
                'type' => 'group',
                'name' => __('Toolbox'),
                'children' => array(
                    array(
                        'text' => __('List All'),
                        'url' => '/tools/index'
                    ),
                    array(
                        'text' => __('Add Tool'),
                        'url' => '/tools/add'
                    )
                )
            ),
            array(
                'type' => 'group',
                'name' => __('Admnistration'),
                'children' => array(
                    array(
                        'text' => __('List Sharing Groups'),
                        'url' => '/sharing_groups/index'
                    ),
                    array(
                        'text' => __('Add Sharing Group'),
                        'url' => '/sharing_groups/add'
                    )
                )
            ),
            array(
                'type' => 'group',
                'name' => __('My Profile'),
                'children' => array(
                    array(
                        'text' => __('View My Profile'),
                        'url' => '/users/view/me'
                    ),
                    array(
                        'text' => __('Modify My Profile'),
                        'url' => '/users/edit/me'
                    )
                )
            )
        )
    );
    echo $this->element('genericElements/header_scaffold', ['menu' => $menu]);
