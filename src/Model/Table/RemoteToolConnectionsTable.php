<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Http\Client;
use Cake\ORM\TableRegistry;
use Cake\Error\Debugger;

class RemoteToolConnectionsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->BelongsTo(
            'LocalTools'
        );
        $this->setDisplayField('id');
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator;
    }
}
