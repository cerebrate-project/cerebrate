<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;

class Alignment extends AppModel
{
        protected $_accessible = [
            'organisation_id' => true,
            'type' => true,
            'id' => false,
            'created' => false
    ];  
}
