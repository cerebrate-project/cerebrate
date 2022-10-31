<?php
namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;

class KeycloakSyncCommand extends Command
{
    protected $defaultTable = 'Users';

    public function execute(Arguments $args, ConsoleIo $io)
    {
        if (!empty(Configure::read('keycloak'))) {
            $this->loadModel('Users');
            $results = $this->fetchTable()->syncWithKeycloak();
            $tableData = [
                ['Modification type', 'Count', 'Affected users']
            ];
            foreach ($results as $k => $v) {
                $tableData[] = [$k, '<text-right>' . count($v) . '</text-right>', '<text-right>' . implode(', ', $v) . '</text-right>'];
            }
            $io->out(__('Sync done. See the results below.'));
            $io->helper('Table')->output($tableData);
        } else {
            $io->error(__('Keycloak is not enabled.'));
        }
    }
}
