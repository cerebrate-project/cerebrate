<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;

class Outbox extends AppModel
{
    protected $_virtual = ['severity_variant'];

    protected function _getSeverityVariant(): string
    {
        return $this->table()->severityVariant[$this->severity];
    }
}
