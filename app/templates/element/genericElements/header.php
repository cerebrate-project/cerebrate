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
    $navdata = '';
    foreach ($menu['collapse'] as $k => $menuElement) {
        if ($menuElement['type'] === 'single') {
            $navdata .= sprintf(
                '<li class="nav-item active"><a class="nav-link %s" href="%s">%s</a>',
                empty($menuElement['class']) ? '' : h($menuElement['class']),
                empty($menuElement['url']) ? '' : h($menuElement['url']),
                empty($menuElement['text']) ? '' : h($menuElement['text'])
            );
        } else if ($menuElement['type'] === 'group') {
            $navdataElements = '';
            foreach ($menuElement['children'] as $child) {
                if (!empty($child['type']) && $child['type'] === 'divider') {
                    $navdataElements .= '<div class="dropdown-divider"></div>';
                } else {
                    $navdataElements .= sprintf(
                        '<a class="dropdown-item %s" href="%s">%s</a>',
                        empty($child['class']) ? '' : h($child['class']),
                        empty($child['url']) ? '' : h($child['url']),
                        empty($child['text']) ? '' : h($child['text'])
                    );
                }
            }
            $navdata .= sprintf(
                '<li class="nav-item dropdown">%s%s</li>',
                sprintf(
                    '<a class="nav-link dropdown-toggle" href="#" id="%s" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">%s</a>',
                    'dropdown-label-' . h($k),
                    h($menuElement['name'])
                ),
                sprintf(
                    '<div class="dropdown-menu" aria-labelledby="navbarDropdown">%s</div>',
                    $navdataElements
                )
            );
        }
    }
    $navdata = sprintf(
        '<div class="collapse navbar-collapse" id="navbarCollapse"><ul class="navbar-nav mr-auto">%s</ul></div>',
        $navdata
    );
    $homeButton = sprintf(
        '<a class="navbar-brand %s" href="%s">%s</a>',
        empty($menu['home']['class']) ? '' : h($menu['home']['class']),
        empty($menu['home']['url']) ? '' : h($menu['home']['url']),
        empty($menu['home']['text']) ? '' : h($menu['home']['text'])

    );
    echo sprintf(
        '<nav class="navbar navbar-expand-lg navbar-dark bg-dark">%s%s%s</nav>',
        $homeButton,
        '<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>',
        $navdata
    );
?>
