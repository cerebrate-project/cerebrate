<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;
use Authentication\PasswordHasher\DefaultPasswordHasher;

class User extends AppModel
{
    protected $_hidden = ['password'];
    protected function _setPassword(string $password) : ?string
    {
        if (strlen($password) > 0) {
            return (new DefaultPasswordHasher())->hash($password);
        }
    }
}
