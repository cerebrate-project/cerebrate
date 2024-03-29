<?php
namespace BreadcrumbNavigation;

require_once(APP . 'Controller' . DS . 'Component' . DS . 'Navigation' . DS . 'base.php'); 

class OrganisationsNavigation extends BaseNavigation
{
    public function addLinks()
    {
        $controller = 'Organisations';
        if (empty($this->viewVars['canEdit'])) {
            $this->bcf->removeLink($controller, 'view', $controller, 'edit');
            $this->bcf->removeLink($controller, 'edit', $controller, 'edit');
        }
    }

    public function addActions()
    {
        $controller = 'Organisations';
        if (empty($this->viewVars['canEdit'])) {
            $this->bcf->removeAction($controller, 'view', $controller, 'delete');
            $this->bcf->removeAction($controller, 'edit', $controller, 'delete');
        }
    }
}
