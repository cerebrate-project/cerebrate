<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;

class Organisation extends AppModel
{
    protected $_accessible = [
        '*' => true,
        'id' => false,
        'uuid' => false,
    ];

    protected $_accessibleOnNew = [
        'uuid' => true,
    ];
}
