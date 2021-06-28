<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;

class SharingGroup extends AppModel
{
    protected $_accessible = [
        '*' => true,
        'id' => false,
        'uuid' => false,
        'organisation_id' => false,
        'user_id' => false,
    ];

    protected $_accessibleOnNew = [
        'uuid' => true,
        'organisation_id' => true,
        'user_id' => true,
    ];

    public function getAccessibleFieldForNew(): array
    {
        return $this->_accessibleOnNew;
    }
}
