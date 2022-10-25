<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;

class Inbox extends AppModel
{
    protected $_virtual = ['local_tool_connector_name', 'severity_variant'];

    protected function _getLocalToolConnectorName()
    {
        $localConnectorName = null;
        if (!empty($this->data) && !empty($this->data['connectorName'])) {
            $localConnectorName = $this->data['connectorName'];
        }
        return $localConnectorName;
    }

    protected function _getSeverityVariant(): string
    {
        return $this->table()->severityVariant[$this->severity];
    }
}
