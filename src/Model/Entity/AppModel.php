<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

class AppModel extends Entity
{
    public function getAccessibleFieldForNew(): array
    {
        return $this->_accessibleOnNew ?? [];
    }
}
