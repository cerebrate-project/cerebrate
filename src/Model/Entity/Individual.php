<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;

class Individual extends AppModel
{
    protected $_accessible = [
        '*' => true,
        'id' => false,
        'uuid' => false,
    ];

    protected $_accessibleOnNew = [
        'uuid' => true,
    ];

    protected $_virtual = ['full_name'];

    protected function _getFullName()
    {
        if (empty($this->first_name) && empty($this->last_name)) {
            return $this->username;
        }
        return sprintf("%s %s", $this->first_name, $this->last_name);
    }
}
