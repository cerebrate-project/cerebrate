<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;

class Inbox extends AppModel
{
    protected $_virtual = ['local_tool_name'];

    protected function _getLocalToolName()
    {
        $localToolName = null;
        if (!empty($this->data) && !empty($this->data['toolName'])) {
            $localToolName = $this->data['toolName'];
        }
        return $localToolName;
    }
}
