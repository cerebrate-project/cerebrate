<?php

namespace App\View\Helper;

use Cake\View\Helper;
use Cake\Utility\Hash;

class HashHelper extends Helper
{
    public function extract($target, $extraction_string)
    {
        return Hash::extract($target, $extraction_string);
    }
}
