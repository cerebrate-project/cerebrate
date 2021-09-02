<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Tagged extends AppModel {

    protected $_accessible = [
        'id' => false,
        '*' => true,
    ];

}
