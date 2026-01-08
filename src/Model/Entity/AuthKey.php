<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;

class AuthKey extends AppModel
{

    protected $_hidden = ['authkey'];

    protected $_accessible = [
            'authkey' => false,
            'authkey_start' => false,
            'authkey_end' => false,
            'expiration' => true,
            'comment' => true,
            'type' => true,
            'id' => false,
            'uuid' => false,
            'created' => false
    ];  
}
