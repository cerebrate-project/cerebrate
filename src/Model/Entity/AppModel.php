<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

class AppModel extends Entity
{
    const BROTLI_HEADER = "\xce\xb2\xcf\x81";
    const BROTLI_MIN_LENGTH = 200;

    const ACTION_ADD = 'add',
        ACTION_EDIT = 'edit',
        ACTION_SOFT_DELETE = 'soft_delete',
        ACTION_DELETE = 'delete',
        ACTION_UNDELETE = 'undelete',
        ACTION_TAG = 'tag',
        ACTION_TAG_LOCAL = 'tag_local',
        ACTION_REMOVE_TAG = 'remove_tag',
        ACTION_REMOVE_TAG_LOCAL = 'remove_local_tag',
        ACTION_LOGIN = 'login',
        ACTION_LOGIN_FAIL = 'login_fail',
        ACTION_LOGOUT = 'logout';


    public function getConstant($name)
    {
        return constant('self::' . $name);
    }

    public function getAccessibleFieldForNew(): array
    {
        return $this->_accessibleOnNew ?? [];
    }
}
