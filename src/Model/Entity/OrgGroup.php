<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;

class OrgGroup extends AppModel
{
    protected $_accessible = [
        '*' => true,
        'id' => false,
        'created' => false
    ];

    protected $_accessibleOnNew = [
        'created' => true
    ];

    public function rearrangeForAPI(array $options = []): void
    {
        if (!empty($this->tags)) {
            $this->tags = $this->rearrangeTags($this->tags);
        }
    }
}
