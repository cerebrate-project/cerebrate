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
        $organisationForNationality = $this->_fetchOrganisationsForNationality($nationality);
        $organisationIDsForNationality = array_keys($organisationForNationality);
        if (empty($organisationIDsForNationality)) {
            $message = sprintf('No changes for organisations with nationality `%s`', $nationality);
            fwrite($file_input, $message);
            $this->io->warning($message);
            return;
        }
        $userForOrg = $this->_fetchUserForOrg($organisationIDsForNationality);
        $userEmailByID = Hash::combine($userForOrg, '{n}.id', '{n}.individual.email');
        $userID = Hash::extract($userForOrg, '{n}.id');
        $individualID = Hash::extract($userForOrg, '{n}.individual_id');

        $message = 'Modified users:' . PHP_EOL;
        fwrite($file_input, $message);
        $this->io->out($message);
        $logsUsers = $this->_fetchLogsForUsers($userID, $days);
        $logsUsers = array_map(function($log) use ($userEmailByID) {
            $userID = $log['model_id'];
            $log['element_id'] = $userID;
            $log['element_display_field'] = $userEmailByID[$userID];
            return $log;
        }, $logsUsers);

        $userByIDs = Hash::combine($userForOrg, '{n}.id', '{n}');
        $logsUserMetaFields = $this->_fetchLogsForUserMetaFields($userID, $days);
        $logsUserMetaFields = $this->_formatUserMetafieldLogs($logsUserMetaFields, $userEmailByID);
        $logsUsersCombined = array_merge($logsUsers, $logsUserMetaFields);
        usort($logsUsersCombined, function($a, $b) {
            return $a['created'] < $b['created'] ? -1 : 1;
        });
        $modifiedUsers = $this->_formatLogsForTable($logsUsersCombined);
        foreach ($modifiedUsers as $row) {
            fputcsv($file_input, $row);
        }
        $this->io->helper('Table')->output($modifiedUsers);

        $message = PHP_EOL . 'Modified organisations:' . PHP_EOL;
        fwrite($file_input, $message);
        $this->io->out($message);
        $logsOrgs = $this->_fetchLogsForOrgs($organisationIDsForNationality, $days);
        $logsOrgs = array_map(function ($log) use ($organisationIDsForNationality) {
            $orgID = $log['model_id'];
            $log['element_id'] = $orgID;
            $log['element_display_field'] = $organisationIDsForNationality[$orgID];
            return $log;
        }, $logsOrgs);
        $modifiedOrgs = $this->_formatLogsForTable($logsOrgs);
        foreach ($modifiedOrgs as $row) {
            fputcsv($file_input, $row);
        }
        $this->io->helper('Table')->output($modifiedOrgs);

        $message = PHP_EOL . 'Modified individuals:' . PHP_EOL;
        fwrite($file_input, $message);
        $this->io->out($message);
        $logsIndividuals = $this->_fetchLogsForIndividuals($individualID, $days);
        $logsIndividuals = array_map(function ($log) use ($userEmailByID) {
            $individualID = $log['model_id'];
            $log['element_id'] = $individualID;
            $log['element_display_field'] = $userEmailByID[$individualID];
            return $log;
        }, $logsIndividuals);
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
        return $this->Organisations->find('list')
            ->where([
                'nationality' => $nationality,
            ])
            ->all()
            ->toArray();
        // return array_keys($this->Organisations->find('list')
        //     ->where([
        //         'nationality' => $nationality,
        //     ])
        //     ->all()
        //     ->toArray());
    }

    protected function _fetchOrgNationalities(): array
    {
        return $this->Organisations->find()
            ->where([
                'nationality !=' => '',
            ])
            ->group('nationality')
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

    protected function _fetchLogsForUserMetaFields(array $userIDs = [], int $days=7): array
    {
        if (empty($userIDs)) {
            return [];
        }
        $logs = $this->_fetchLogs([
            'contain' => ['Users'],
            'conditions' => [
                'model' => 'MetaFields',
                'request_action IN' => ['add', 'edit', 'delete'],
                'AuditLogs.created >=' => FrozenTime::now()->subDays($days),
            ]
        ]);
        $metaFieldLogs = array_filter($logs, function ($log) use ($userIDs) {
            return !empty($log['changed']['scope']) && $log['changed']['scope'] === 'user' && in_array($log['changed']['parent_id'], $userIDs);
        });
        $metaFieldLogs = array_map(function ($log) {
            $log['modified_user_id'] = $log['changed']['parent_id'];
            return $log;
        }, $metaFieldLogs);
        $metaFieldDeletionLogs = array_filter($logs, function ($log) {
            return $log['request_action'] === 'delete';
        });
        $allLogs = $metaFieldLogs;
        foreach ($metaFieldDeletionLogs as $i => $log) {
            $latestAssociatedLog = $this->_fetchLogs([
                'contain' => ['Users'],
                'conditions' => [
                    'model' => 'MetaFields',
                    'request_action IN' => ['add'],
                    'model_id' => $log['model_id'],
                ],
                'order' => ['AuditLogs.created' => 'DESC'],
                'limit' => 1,
            ]);
            if (!empty($latestAssociatedLog)) {
                if (in_array($latestAssociatedLog[0]['changed']['parent_id'], $userIDs)) {
                    $log['changed']['orig_value'] = $latestAssociatedLog[0]['changed']['value'];
                    $log['changed']['value'] = '';
                    $log['modified_user_id'] = $latestAssociatedLog[0]['changed']['parent_id'];
                    $allLogs[] = $log;
                }
            }
        }
        return $allLogs;
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
        $query = $this->AuditLogs->find()
            ->contain($options['contain'])
            ->where($options['conditions']);
        if (!empty($options['order'])) {
            $query = $query->order($options['order']);
        }
        if (!empty($options['limit'])) {
            $query = $query
                ->limit($options['limit'])
                ->page(1);
        }
        $logs = $query
            ->enableHydration(false)
            ->all()->toList();
        return array_map(function ($log) {
            $log['changed'] = is_resource($log['changed']) ? stream_get_contents($log['changed']) : $log['changed'];
            $log['changed'] = json_decode($log['changed'], true);
            return $log;
        }, $logs);
    }

    protected function _formatUserMetafieldLogs($logEntries, $userEmailByID): array
    {
        return array_map(function($log) use ($userEmailByID) {
            $log['model'] = 'Users';
            $log['request_action'] = 'edit';
            $log['changed'] = [
                $log['model_title'] => [
                    $log['changed']['orig_value'] ?? '',
                    $log['changed']['value']
                ]
            ];
            $log['element_id'] = $log['modified_user_id'];
            $log['element_display_field'] = $userEmailByID[$log['modified_user_id']];
            return $log;
        }, $logEntries);
    }

    protected function _formatLogsForTable($logEntries): array
    {
        $header = ['Model', 'Action', 'Editor user', 'Log ID', 'Datetime', 'Modified  element ID', 'Modified element', 'Change'];
        $data = [$header];
        foreach ($logEntries as $logEntry) {
            $formatted = [
                $logEntry['model'],
                $logEntry['request_action'],
                sprintf('%s (%s)', $logEntry['user']['username'], $logEntry['user_id']),
                $logEntry['id'],
                $logEntry['created']->i18nFormat('yyyy-MM-dd HH:mm:ss'),
                $logEntry['element_id'] ?? '-',
                $logEntry['element_display_field'] ?? '-',
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
