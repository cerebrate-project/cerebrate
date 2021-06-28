<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;

class Inbox extends AppModel
{
    protected $_virtual = ['local_tool_connector_name'];

    protected function _getLocalToolConnectorName()
    {
        $localConnectorName = null;
        if (!empty($this->data) && !empty($this->data['connectorName'])) {
            $localConnectorName = $this->data['connectorName'];
        }
        return $localConnectorName;
    }
}
