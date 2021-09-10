<?php
    use Cake\Core\Configure;
    use Cake\Routing\Router;

    $controller = $this->request->getParam('controller');
    $action = $this->request->getParam('action');
    $curentPath = "{$controller}{$action}";
    $navbarVariant = Configure::read('navbarVariant');
    $navbarIsDark = Configure::read('navbarIsDark');

    $breadcrumbLinks = '';
    $breadcrumbAction = '';
    $this->Breadcrumbs->setTemplates([
        'wrapper' => sprintf(
            '<nav class="header-breadcrumb d-xl-block d-none"{{attrs}}><ol class="">{{content}}</ol></nav>'
        ),
        'item' => sprintf(
            '<li class="header-breadcrumb-item"{{attrs}}><i class="{{icon}} mr-1"></i><a href="{{url}}"{{innerAttrs}}>{{title}}</a></li>{{separator}}',
            empty($darkMode) ? 'light' : 'dark'
        ),
        'itemWithoutLink' => '<li class="header-breadcrumb-item"{{attrs}}><span{{innerAttrs}}>{{title}}</span></li>{{separator}}',
        'separator' => '<li class="header-breadcrumb-separator"{{attrs}}><span{{innerAttrs}}><i class="fa fa-sm fa-angle-right"></i></span></li>'
    ]);

    if (!empty($breadcrumb)) {
        foreach ($breadcrumb as $entry) {
            if (!empty($entry['textGetter'])) {
                $entry['label'] = Cake\Utility\Hash::get($entity, $entry['textGetter']);
            }
            if (!empty($entry['url_vars'])) {
                $entry['url'] = $this->DataFromPath->buildStringFromDataPath($entry['url'], $entity, $entry['url_vars']);
            }
            $this->Breadcrumbs->add(h($entry['label']), Router::url($entry['url']), [
                'title' => h($entry['label']),
                'templateVars' => [
                    'icon' => !empty($entry['icon']) ? $this->FontAwesome->getClass(h($entry['icon'])) : ''
                ]
            ]);
        }
    
        $lastCrumb = $breadcrumb[count($breadcrumb)-1];
        if (!empty($lastCrumb['links']) || !empty($lastCrumb['actions'])) {
            $this->Breadcrumbs->add([[]]); // add last separetor
        }
    
        if (!empty($lastCrumb['links'])) {
            foreach ($lastCrumb['links'] as $i => $linkEntry) {
                $active = $linkEntry['route_path'] == $lastCrumb['route_path'];
                if (!empty($linkEntry['url_vars'])) {
                    $linkEntry['url'] = $this->DataFromPath->buildStringFromDataPath($linkEntry['url'], $entity, $linkEntry['url_vars']);
                }
                $breadcrumbLinks .= sprintf('<a class="btn btn-%s btn-sm text-nowrap" role="button" href="%s">%s</a>',
                    $active ? 'secondary' : $navbarVariant,
                    Router::url($linkEntry['url']),
                    h($linkEntry['label'])
                );
            }
        }
        if (!empty($lastCrumb['actions'])) {
            foreach ($lastCrumb['actions'] as $i => $actionEntry) {
                if (!empty($actionEntry['url_vars'])) {
                    $actionEntry['url'] = $this->DataFromPath->buildStringFromDataPath($actionEntry['url'], $entity, $actionEntry['url_vars']);
                }
                $breadcrumbAction .= sprintf('<a class="dropdown-item" href="%s">%s</a>', Router::url($actionEntry['url']), h($actionEntry['label']));
            }
        }
    }

?>

<?php
    echo $this->Breadcrumbs->render(
        [],
        ['separator' => '']
    );
?>
<?php if (!empty($breadcrumbLinks)): ?>
    <div class="header-breadcrumb-children d-none d-md-flex">
        <?= $breadcrumbLinks ?>
    </div>
<?php endif; ?>

<?php if (!empty($breadcrumbAction)): ?>
<div class="header-breadcrumb-actions dropdown d-flex align-items-center">
    <a class="btn btn-primary dropdown-toggle" href="#" role="button" id="dropdownMenuBreadcrumbAction" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <?= __('Actions') ?>
    </a>
    <div class="dropdown-menu" aria-labelledby="dropdownMenuBreadcrumbAction">
        <?= $breadcrumbAction ?>
    </div>
</div>
<?php endif; ?>