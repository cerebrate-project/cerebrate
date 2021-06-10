<?php

namespace CommonConnectorTools;
use Cake\ORM\Locator\LocatorAwareTrait;

class CommonConnectorTools
{
    public $description = '';
    public $name = '';
    public $exposedFunctions = [
        'diagnostics'
    ];
    public $version = '???';

    public function addExposedFunction(string $functionName): void
    {
        $this->exposedFunctions[] = $functionName;
    }

    public function runAction($action, $params) {
        if (!in_array($action, $exposedFunctions)) {
            throw new MethodNotAllowedException(__('Invalid connector function called.'));
        }
        return $this->{$action}($params);
    }

    public function health(Object $connection): array
    {
        return 0;
    }

    public function captureOrganisation($input): bool
    {
        if (empty($input['uuid'])) {
            return false;
        }
        $organisations = \Cake\ORM\TableRegistry::getTableLocator()->get('Organisations');
        $organisations->captureOrg($input);
        return true;
    }

    public function captureSharingGroup($input): bool
    {
        if (empty($input['uuid'])) {
            return false;
        }
        $sharing_groups = \Cake\ORM\TableRegistry::getTableLocator()->get('SharingGroups');
        $sharing_groups->captureSharingGroup($input);
        return true;
    }

    public function encodeConnection(array $params): array
    {
        $result = $this->encodeConnection($params);
        return $result;
    }
}

?>
