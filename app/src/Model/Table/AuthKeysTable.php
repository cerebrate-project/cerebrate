<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use Cake\Utility\Security;
use ArrayObject;

class AuthKeysTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->belongsTo(
            'Users'
        );
        $this->setDisplayField('authkey');
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        $data['created'] = time();
        if (empty($data['valid_until'])) {
            $data['valid_until'] = 0;
        }
        if (empty($data['authkey'])) {
            $data['authkey'] = $this->generateAuthKey();
        }
    }

    public function generateAuthKey()
    {
        return Security::randomString(40);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('authkey')
            ->notEmptyString('user_id')
            ->requirePresence(['authkey', 'user_id'], 'create');
        return $validator;
    }
}
