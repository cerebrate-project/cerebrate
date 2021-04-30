<?php

namespace CommonConnectorTools;

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
}

?>
