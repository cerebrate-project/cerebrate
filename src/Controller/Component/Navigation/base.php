<?php
namespace BreadcrumbNavigation;

class BaseNavigation
{
    protected $bcf;
    protected $request;
    public $currentUser;

    public function __construct($bcf, $request)
    {
        $this->bcf = $bcf;
        $this->request = $request;
    }

    public function setCurrentUser($currentUser)
    {
        $this->currentUser = $currentUser;
    }

    public function addRoutes() {}
    public function addParents() {}
    public function addLinks() {}
    public function addActions() {}
}
