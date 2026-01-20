<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;

class EncryptionKey extends AppModel
{
    protected $_accessible = [
        '*' => true,
        'id' => false,
        'uuid' => false,
    ];

    public function rearrangeForAPI(array $options = []): void
    {
        $this->rearrangeSimplify(['organisation', 'individual']);
    }
}
