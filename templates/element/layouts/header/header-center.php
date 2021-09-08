<?php
    $this->Breadcrumbs->add([
        ['title' => 'Broods', 'url' => ['controller' => 'Broods', 'action' => 'index']],
        ['title' => 'Self', 'url' => ['controller' => 'Broods', 'action' => 'view', 4]],
        []
    ]);
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
    echo $this->Breadcrumbs->render(
        [],
        ['separator' => '']
    );
?>
<div class="header-breadcrumb-children">
    <a class="btn btn-secondary btn-sm" role="button" href="#">View</a>
    <a class="btn btn-primary btn-sm" role="button" href="#">Local tools</a>
</div>