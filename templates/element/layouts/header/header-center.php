<?php
    use Cake\Core\Configure;

    $controller = $this->request->getParam('controller');
    $action = $this->request->getParam('action');
    $curentPath = "{$controller}{$action}";
    $navbarVariant = Configure::read('navbarVariant');
    $navbarIsDark = Configure::read('navbarIsDark');
    // $pass = $this->request->getParam('pass');

    $breadcrumbLinks = '';
    $breadcrumbAction = '';
    $this->Breadcrumbs->setTemplates([
        'wrapper' => sprintf(
            '<nav class="header-breadcrumb"{{attrs}}><ol class="">{{content}}</ol></nav>'
        ),
        'item' => sprintf(
            '<li class="header-breadcrumb-item"{{attrs}}><a href="{{url}}"{{innerAttrs}}>{{title}}</a></li>{{separator}}',
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
            $this->Breadcrumbs->add($entry['label'], $entry['url']);
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
                    $linkEntry['url'],
                    $linkEntry['label']
                );
            }
        }
        if (!empty($lastCrumb['actions'])) {
            foreach ($lastCrumb['actions'] as $i => $actionEntry) {
                if (!empty($actionEntry['url_vars'])) {
                    $actionEntry['url'] = $this->DataFromPath->buildStringFromDataPath($actionEntry['url'], $entity, $actionEntry['url_vars']);
                }
                $breadcrumbAction .= sprintf('<a class="dropdown-item" href="%s">%s</a>', $actionEntry['url'], $actionEntry['label']);
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
    <div class="header-breadcrumb-children">
        <?= $breadcrumbLinks ?>
    </div>
<?php endif; ?>

<?php if (!empty($breadcrumbAction)): ?>
<div class="dropdown ml-3 d-flex align-items-center">
    <a class="btn btn-primary dropdown-toggle" href="#" role="button" id="dropdownMenuBreadcrumbAction" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <?= __('Actions') ?>
    </a>
    <div class="dropdown-menu" aria-labelledby="dropdownMenuBreadcrumbAction">
        <?= $breadcrumbAction ?>
    </div>
</div>
<?php endif; ?>