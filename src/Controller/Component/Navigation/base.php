<?php
namespace BreadcrumbNavigation;

class BaseNavigation
{
    protected $bcf;
    protected $request;
    protected $viewVars;

    public function __construct($bcf, $request, $viewVars)
    {
        $this->bcf = $bcf;
        $this->request = $request;
        $this->viewVars = $viewVars;
    }

    public function addRoutes() {}
    public function addParents() {}
    public function addLinks() {}
    public function addActions() {}
}