<?php
$navdata = '';
$i = 0;
foreach ($data['menu'] as $name => $menuElement) {
    $i++;
    if (!empty($menuElement['skipTopMenu'])) {
        continue;
    }
    if (!empty($menuElement['type']) === 'single' && $menuElement['type'] === 'single') {
        $navdata .= sprintf(
            '<li class="nav-item active"><a class="nav-link %s" href="%s">%s</a>',
            empty($menuElement['class']) ? '' : h($menuElement['class']),
            empty($menuElement['url']) ? '' : h($menuElement['url']),
            empty($name) ? '' : h($name)
        );
    } else if (empty($menuElement['type']) || $menuElement['type'] === 'group') {
        $navdataElements = '';
        $first = true;
        foreach ($menuElement as $subCategory => $subCategoryData) {
            if (!empty($subCategoryData['skipTopMenu'])) {
                continue;
            }
            if (!$first) {
                $navdataElements .= '<div class="dropdown-divider"></div>';
            }
            $first = false;
            foreach ($subCategoryData['children'] as $child) {
                if (!empty($child['skipTopMenu'])) {
                    continue;
                }
                $navdataElements .= sprintf(
                    '<a class="dropdown-item %s" href="%s">%s</a>',
                    empty($child['class']) ? '' : h($child['class']),
                    empty($child['url']) ? '' : h($child['url']),
                    empty($child['label']) ? '' : h($child['label'])
                );
            }
        }
        $navdata .= sprintf(
            '<li class="nav-item dropdown">%s%s</li>',
            sprintf(
                '<a class="nav-link dropdown-toggle" href="#" id="%s" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">%s</a>',
                'dropdown-label-' . h($i),
                h($name)
            ),
            sprintf(
                '<div class="dropdown-menu" aria-labelledby="navbarDropdown">%s</div>',
                $navdataElements
            )
        );
    }
}
$logoutButton = sprintf(
    '<span class="nav-item"><a href="/users/logout" class="nav-link">%s</a></span>',
    __('Logout')
);
$navdata = sprintf(
    '<div class="collapse navbar-collapse" id="navbarCollapse"><ul class="navbar-nav mr-auto">%s%s</ul></div>',
    $navdata,
    $logoutButton
);
$homeButton = sprintf(
    '<a class="navbar-brand %s" href="%s">%s</a>',
    empty($data['home']['class']) ? '' : h($data['home']['class']),
    empty($data['home']['url']) ? '' : h($data['home']['url']),
    empty($data['home']['text']) ? '' : h($data['home']['text'])
);
echo sprintf(
    '<nav class="navbar navbar-expand-lg navbar-dark %s">%s%s%s</nav>',
    $darkMode ? 'bg-primary' : 'bg-dark',
    $homeButton,
    '<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>',
    $navdata
);
