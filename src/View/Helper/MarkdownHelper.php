<?php

namespace App\View\Helper;

use Cake\View\Helper;
use Cake\Utility\Hash;
use Parsedown;

class MarkdownHelper extends Helper
{
    private $Parsedown = null;

    public function initialize(array $config): void
    {
        $this->Parsedown = new Parsedown();
    }

    public function text($input)
    {
        return $this->Parsedown->text($input);
    }

    public function line($input)
    {
        return $this->Parsedown->line($input);
    }
}
