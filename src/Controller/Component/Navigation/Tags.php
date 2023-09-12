<?php
namespace BreadcrumbNavigation;

require_once(APP . 'Controller' . DS . 'Component' . DS . 'Navigation' . DS . 'base.php'); 

class TagsNavigation extends BaseNavigation
{
    public function addLinks()
    {
        $controller = 'Tags';
        if (empty($this->viewVars['loggedUser']['role']['perm_admin'])) {
            $this->bcf->removeLink($controller, 'view', $controller, 'edit');
            $this->bcf->removeLink($controller, 'edit', $controller, 'edit');
        }
    }

    public function addActions()
    {
        $controller = 'Tags';
        if (empty($this->viewVars['loggedUser']['role']['perm_admin'])) {
            $this->bcf->removeAction($controller, 'view', $controller, 'delete');
            $this->bcf->removeAction($controller, 'edit', $controller, 'delete');
        }
    }
}
