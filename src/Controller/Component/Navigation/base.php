<?php
namespace BreadcrumbNavigation;

class BaseNavigation
{
    protected $bcf;
    protected $request;

    public function __construct($bcf, $request)
    {
        $this->bcf = $bcf;
        $this->request = $request;
    }

    public function addRoutes() {}
    public function addParents() {}
    public function addLinks() {}
    public function addActions() {}
}