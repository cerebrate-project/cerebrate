<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;

class PhinxlogTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function mergeMigrationLogIntoStatus(array $status): array
    {
        $logs = $this->find('list', [
            'keyField' => 'version',
            'valueField' => function ($entry) {
                return $entry;
            }
        ])->toArray();
        foreach ($status as &$entry) {
            if (!empty($logs[$entry['id']])) {
                $logEntry = $logs[$entry['id']];
                $startTime = $logEntry['start_time'];
                $startTime->setToStringFormat('yyyy-MM-dd HH:mm:ss');
                $endTime = $logEntry['end_time'];
                $endTime->setToStringFormat('yyyy-MM-dd HH:mm:ss');
                $timeTaken = $logEntry['end_time']->diff($logEntry['start_time']);
                $timeTakenFormated = sprintf('%s min %s sec',
                    floor(abs($logEntry['end_time']->getTimestamp() - $logEntry['start_time']->getTimestamp()) / 60),
                    abs($logEntry['end_time']->getTimestamp() - $logEntry['start_time']->getTimestamp()) % 60
                );
            } else {
                $startTime = 'N/A';
                $endTime = 'N/A';
                $timeTaken = 'N/A';
                $timeTakenFormated = 'N/A';
            }
            $entry['start_time'] = $startTime;
            $entry['end_time'] = $endTime;
            $entry['time_taken'] = $timeTaken;
            $entry['time_taken_formated'] = $timeTakenFormated;
        }
        return $status;
    }
}
