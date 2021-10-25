<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;

class MailingList extends AppModel
{
    protected $_accessible = [
        '*' => true,
        'id' => false,
        'uuid' => false,
        'user_id' => false,
    ];

    protected $_accessibleOnNew = [
        'uuid' => true,
        'user_id' => true,
    ];
}
