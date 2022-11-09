<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;

class EncryptionKey extends AppModel
{

    public function rearrangeForAPI(): void
    {
        $this->rearrangeSimplify(['organisation', 'individual']);
    }
}
