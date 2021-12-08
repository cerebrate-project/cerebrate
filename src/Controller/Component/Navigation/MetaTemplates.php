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
            'icon' => 'check-square',
            'url' => '/metaTemplates/enable/{{id}}/enabled',
            'url_vars' => ['id' => 'id'],
        ]);
        $this->bcf->addRoute('MetaTemplates', 'set_default', [
            'label' => __('Set as default'),
            'icon' => 'check-square',
            'url' => '/metaTemplates/toggle/{{id}}/default',
            'url_vars' => ['id' => 'id'],
        ]);

        $totalUpdateCount = 0;
        if (!empty($this->viewVars['updateableTemplate']['updateable']) && !empty($this->viewVars['updateableTemplate']['new'])) {
            $udpateCount = count($this->viewVars['updateableTemplate']['updateable']) ?? 0;
            $newCount = count($this->viewVars['updateableTemplate']['new']) ?? 0;
            $totalUpdateCount = $udpateCount + $newCount;
        }
        $updateRouteConfig = [
            'label' => __('Update all templates'),
            'icon' => 'download',
            'url' => '/metaTemplates/update',
        ];
        if ($totalUpdateCount > 0) {
            $updateRouteConfig['badge'] = [
                'text' => h($totalUpdateCount),
                'variant' => 'warning',
                'title' => __('There are {0} new meta-template(s) and {1} update(s) available', h($newCount), h($udpateCount)),
            ];
        }
        $this->bcf->addRoute('MetaTemplates', 'update', $updateRouteConfig);
    }

    public function addParents()
    {
        $this->bcf->addParent('MetaTemplates', 'view', 'MetaTemplates', 'index');
        $this->bcf->addParent('MetaTemplates', 'update', 'MetaTemplates', 'index');
    }

    public function addLinks()
    {
        $this->bcf->addSelfLink('MetaTemplates', 'view');
    }

    public function addActions()
    {
        $totalUpdateCount = 0;
        if (!empty($this->viewVars['updateableTemplate']['not_up_to_date']) && !empty($this->viewVars['updateableTemplate']['new'])) {
            $udpateCount = count($this->viewVars['updateableTemplate']['not_up_to_date']) ?? 0;
            $newCount = count($this->viewVars['updateableTemplate']['new']) ?? 0;
            $totalUpdateCount = $udpateCount + $newCount;
        }
        $updateActionConfig = [
            'label' => __('Update template'),
            'url' => '/metaTemplates/update/{{id}}',
            'url_vars' => ['id' => 'id'],
        ];
        if ($totalUpdateCount > 0) {
            $updateActionConfig['badge'] = [
                'text' => h($totalUpdateCount),
                'variant' => 'warning',
                'title' => __('There are {0} new meta-template(s) and {1} update(s) available', h($newCount), h($udpateCount)),
            ];
        }
        $this->bcf->addAction('MetaTemplates', 'index', 'MetaTemplates', 'update', $updateActionConfig);

        if (empty($this->viewVars['updateableTemplate']['up-to-date'])) {
            $this->bcf->addAction('MetaTemplates', 'view', 'MetaTemplates', 'update', [
                'label' => __('Update template'),
                'url' => '/metaTemplates/update/{{uuid}}',
                'url_vars' => ['uuid' => 'uuid'],
                'variant' => 'warning',
                'badge' => [
                    'variant' => 'warning',
                    'title' => __('Update available')
                ]
            ]);
        }
        $this->bcf->addAction('MetaTemplates', 'view', 'MetaTemplates', 'enable');
        $this->bcf->addAction('MetaTemplates', 'view', 'MetaTemplates', 'set_default');
    }
}
