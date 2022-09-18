<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;

class BruteforcesTable extends AppTable
{
    private $logModel = null;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setDisplayField('email');
        $this->logModel = TableRegistry::getTableLocator()->get('AuditLogs');
    }

    public function insert($ip, $username)
    {
        $expire = 300;
        $amount = 5;
        $expire = time() + $expire;
        $expire = date('Y-m-d H:i:s', $expire);
        $bruteforceEntry = $this->newEntity([
            'user_ip' => $ip,
            'username' => trim(strtolower($username)),
            'expiration' => $expire
        ]);
        $this->save($bruteforceEntry);
        $title = 'Failed login attempt using username ' . $username . ' from IP: ' . $ip . '.';
        if ($this->isBlocklisted($ip, $username)) {
            $title .= 'This has tripped the bruteforce protection after  ' . $amount . ' failed attempts. The user is now blocklisted for ' . $expire . ' seconds.';
        }
        $this->logModel->insert([
            'request_action' => 'login_fail',
            'model' => 'Users',
            'model_id' => 0,
            'model_title' => 'bruteforce_block',
            'changed' => []
        ]);
    }

    public function clean()
    {
        $expire = date('Y-m-d H:i:s', time());
        $this->deleteAll(['expiration <=' => $expire]);
    }

    public function isBlocklisted($ip, $username)
    {
        // first remove old expired rows
        $this->clean();
        // count
        $count = $this->find('all', [
            'conditions' => [
                'user_ip' => $ip,
                'username' => trim($username)
            ]
        ])->count();
        if ($count >= 5) {
            return true;
        } else {
            return false;
        }
    }
}
