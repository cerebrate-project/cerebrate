<?php

/**
 * 
 *
 */

namespace App\Command;

use Cake\Console\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Filesystem\Folder;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Http\Client;
use Cake\I18n\FrozenTime;

class SummaryCommand extends Command
{
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Create a summary for data associated to the passed nationality that has been modified. Summaries will be printed out in STDIN and written in individual `txt` files.');
        $parser->addArgument('nationality', [
            'short' => 'n',
            'help' => 'The organisation nationality.',
            'required' => false
        ]);
        $parser->addOption('days', [
            'short' => 'd',
            'help' => 'The amount of days to look back in the logs',
            'default' => 7
        ]);
        $parser->addOption('output', [
            'short' => 'o',
            'help' => 'The destination folder where to write the files',
            'default' => '/tmp'
        ]);
        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io)
    {
        $this->__loadTables();
        $this->io = $io;
        $nationality = $args->getArgument('nationality');
        $days = $args->getOption('days');
        if (!is_null($nationality)) {
            $nationalities = [$nationality];
        } else {
            $nationalities = $this->_fetchOrgNationalities();
        }
        foreach ($nationalities as $nationality) {
            $this->io->out(sprintf('Nationality: %s', $nationality));
            $this->_collectChangedForNationality($nationality, $days, $args->getOption('output'));
            $this->io->out($io->nl(2));
            $this->io->hr();
        }
    }

    protected function _collectChangedForNationality($nationality, $days, $folderPath)
    {
        $folderPath = rtrim($folderPath, '/');
        $filename = sprintf('%s/%s.txt', $folderPath, $nationality);
        $file_input = fopen($filename, 'w');
        $organisationIDsForNationality = $this->_fetchOrganisationsForNationality($nationality);
        if (empty($organisationIDsForNationality)) {
            $message = sprintf('No changes for organisations with nationality `%s`', $nationality);
            fwrite($file_input, $message);
            $this->io->warning($message);
            return;
        }
        $userForOrg = $this->_fetchUserForOrg($organisationIDsForNationality);
        $userID = Hash::extract($userForOrg, '{n}.id');
        $individualID = Hash::extract($userForOrg, '{n}.individual_id');

        $message = 'Modified users:' . PHP_EOL;
        fwrite($file_input, $message);
        $this->io->out($message);
        $logsUsers = $this->_fetchLogsForUsers($userID, $days);
        $modifiedUsers = $this->_formatLogsForTable($logsUsers);
        foreach ($modifiedUsers as $row) {
            fputcsv($file_input, $row);
        }
        $this->io->helper('Table')->output($modifiedUsers);

        $message = PHP_EOL . 'Modified organisations:' . PHP_EOL;
        fwrite($file_input, $message);
        $this->io->out($message);
        $logsOrgs = $this->_fetchLogsForOrgs($organisationIDsForNationality, $days);
        $modifiedOrgs = $this->_formatLogsForTable($logsOrgs);
        foreach ($modifiedOrgs as $row) {
            fputcsv($file_input, $row);
        }
        $this->io->helper('Table')->output($modifiedOrgs);

        $message = PHP_EOL . 'Modified individuals:' . PHP_EOL;
        fwrite($file_input, $message);
        $this->io->out($message);
        $logsIndividuals = $this->_fetchLogsForIndividuals($individualID, $days);
        $modifiedIndividuals = $this->_formatLogsForTable($logsIndividuals);
        foreach ($modifiedIndividuals as $row) {
            fputcsv($file_input, $row);
        }
        $this->io->helper('Table')->output($modifiedIndividuals);
        fclose($file_input);
    }

    private function __loadTables()
    {
        $tables = ['Users', 'Organisations', 'Individuals', 'AuditLogs'];
        foreach ($tables as $table) {
            $this->loadModel($table);
        }
    }

    protected function _fetchOrganisationsForNationality(string $nationality): array
    {
        return array_keys($this->Organisations->find('list')
            ->where([
                'nationality' => $nationality,
            ])
            ->all()
            ->toArray());
    }

    protected function _fetchOrgNationalities(): array
    {
        return $this->Organisations->find()
            ->where([
                'nationality !=' => '',
            ])
            ->all()
            ->extract('nationality')
            ->toList();
    }

    protected function _fetchUserForOrg(array $orgIDs = []): array
    {
        if (empty($orgIDs)) {
            return [];
        }
        return $this->Users->find()
            ->contain(['Individuals', 'Roles', 'UserSettings', 'Organisations'])
            ->where([
                'Organisations.id IN' => $orgIDs,
            ])
            ->enableHydration(false)
            ->all()->toList();
    }

    protected function _fetchLogsForUsers(array $userIDs = [], int $days=7): array
    {
        if (empty($userIDs)) {
            return [];
        }
        return $this->_fetchLogs([
            'contain' => ['Users'],
            'conditions' => [
                'model' => 'Users',
                'request_action IN' => ['add', 'edit', 'delete'],
                'model_id IN' => $userIDs,
                'AuditLogs.created >=' => FrozenTime::now()->subDays($days),
            ]
        ]);
    }

    protected function _fetchLogsForOrgs(array $orgIDs = [], int $days = 7): array
    {
        if (empty($orgIDs)) {
            return [];
        }
        return $this->_fetchLogs([
            'contain' => ['Users'],
            'conditions' => [
                'model' => 'Organisations',
                'request_action IN' => ['add', 'edit', 'delete'],
                'model_id IN' => $orgIDs,
                'AuditLogs.created >=' => FrozenTime::now()->subDays($days),
            ]
        ]);
    }

    protected function _fetchLogsForIndividuals(array $individualID = [], int $days = 7): array
    {
        if (empty($individualID)) {
            return [];
        }
        return $this->_fetchLogs([
            'contain' => ['Users'],
            'conditions' => [
                'model' => 'Individuals',
                'request_action IN' => ['add', 'edit', 'delete'],
                'model_id IN' => $individualID,
                'AuditLogs.created >=' => FrozenTime::now()->subDays($days),
            ]
        ]);
    }

    protected function _fetchLogs(array $options=[]): array
    {
        $logs = $this->AuditLogs->find()
            ->contain($options['contain'])
            ->where($options['conditions'])
            ->enableHydration(false)
            ->all()->toList();
        return array_map(function ($log) {
            $log['changed'] = is_resource($log['changed']) ? stream_get_contents($log['changed']) : $log['changed'];
            $log['changed'] = json_decode($log['changed']);
            return $log;
        }, $logs);
    }

    protected function _formatLogsForTable($logEntries): array
    {
        $header = ['Model', 'Action', 'Editor user', 'Log ID', 'Datetime', 'Change'];
        $data = [$header];
        foreach ($logEntries as $logEntry) {
            $formatted = [
                $logEntry['model'],
                $logEntry['request_action'],
                sprintf('%s (%s)', $logEntry['user']['username'], $logEntry['user_id']),
                $logEntry['id'],
                $logEntry['created']->i18nFormat('yyyy-MM-dd HH:mm:ss'),
            ];
            if ($logEntry['request_action'] == 'edit') {
                $formatted[] = json_encode($logEntry['changed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                $formatted[] = json_encode($logEntry['changed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $data[] = $formatted;
        }
        return $data;
    }
}
