<?php
namespace BreadcrumbNavigation;

require_once(APP . 'Controller' . DS . 'Component' . DS . 'Navigation' . DS . 'base.php'); 

class IndividualsNavigation extends BaseNavigation
{
    public function addLinks()
    {
        $controller = 'Individuals';
        if (empty($this->viewVars['canEdit'])) {
            $this->bcf->removeLink($controller, 'view', $controller, 'edit');
            $this->bcf->removeLink($controller, 'edit', $controller, 'edit');
        }
    }

    public function addActions()
    {
        $controller = 'Individuals';
        if (empty($this->viewVars['canDelete'])) {
            $this->bcf->removeAction($controller, 'view', $controller, 'delete');
            $this->bcf->removeAction($controller, 'edit', $controller, 'delete');
        }
    }
}
