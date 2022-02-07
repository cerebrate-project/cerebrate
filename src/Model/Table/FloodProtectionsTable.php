<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class FloodProtectionsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setDisplayField('request_ip');
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator;
    }
}
