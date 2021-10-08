<?php
namespace BreadcrumbNavigation;

require_once(APP . 'Controller' . DS . 'Component' . DS . 'Navigation' . DS . 'base.php'); 

class UsersNavigation extends BaseNavigation
{
    public function addLinks()
    {
        $bcf = $this->bcf;
        $request = $this->request;
        $this->bcf->addLink('Users', 'view', 'UserSettings', 'index', function ($config) use ($bcf, $request) {
            if (!empty($this->passedData[0])) {
                $user_id = $this->passedData[0];
                $linkData = [
                    'label' => __('User Setting ({0})', h($user_id)),
                    'url' => sprintf('/user-settings/index?Users.id=%s', h($user_id))
                ];
                return $linkData;
            }
            return [];
        });
        $this->bcf->addLink('Users', 'edit', 'UserSettings', 'index', function ($config) use ($bcf, $request) {
            if (!empty($this->passedData[0])) {
                $user_id = $this->passedData[0];
                $linkData = [
                    'label' => __('User Setting ({0})', h($user_id)),
                    'url' => sprintf('/user-settings/index?Users.id=%s', h($user_id))
                ];
                return $linkData;
            }
            return [];
        });
    }
}
