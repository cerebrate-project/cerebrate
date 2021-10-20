<?php

namespace BreadcrumbNavigation;

require_once(APP . 'Controller' . DS . 'Component' . DS . 'Navigation' . DS . 'base.php'); 

class MetaTemplatesNavigation extends BaseNavigation
{
    function addRoutes()
    {
        $this->bcf->addRoute('MetaTemplates', 'index', $this->bcf->defaultCRUD('MetaTemplates', 'index'));
        $this->bcf->addRoute('MetaTemplates', 'view', $this->bcf->defaultCRUD('MetaTemplates', 'view'));
        $this->bcf->addRoute('MetaTemplates', 'enable', [
            'label' => __('Enable'),
            'icon' => 'check',
            'url' => '/metaTemplates/enable/{{id}}/enabled',
            'url_vars' => ['id' => 'id'],
        ]);
        $this->bcf->addRoute('MetaTemplates', 'set_default', [
            'label' => __('Set as default'),
            'icon' => 'check',
            'url' => '/metaTemplates/toggle/{{id}}/default',
            'url_vars' => ['id' => 'id'],
        ]);
    }

    public function addParents()
    {
        $this->bcf->addParent('MetaTemplates', 'view', 'MetaTemplates', 'index');
    }

    public function addLinks()
    {
        $this->bcf->addSelfLink('MetaTemplates', 'view');
    }

    public function addActions()
    {
        $this->bcf->addAction('MetaTemplates', 'view', 'MetaTemplates', 'enable');
        $this->bcf->addAction('MetaTemplates', 'view', 'MetaTemplates', 'set_default');
    }
}
