<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;
use Authentication\PasswordHasher\DefaultPasswordHasher;

class User extends AppModel
{
    protected $_hidden = ['password', 'confirm_password'];

    protected $_virtual = ['user_settings_by_name'];

    protected function _getUserSettingsByName()
    {
        $settingsByName = [];
        if (!empty($this->user_settings)) {
            foreach ($this->user_settings as $i => $setting) {
                $settingsByName[$setting->name] = $setting;
            }
        }
        return $settingsByName;
    }

    protected function _setPassword(string $password) : ?string
    {
        if (strlen($password) > 0) {
            return (new DefaultPasswordHasher())->hash($password);
        }
    }
}
