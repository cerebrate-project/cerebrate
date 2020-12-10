<?php

namespace App\View\Helper;

use Cake\View\Helper;
use Cake\Utility\Hash;

class StringFromPathHelper extends Helper
{
    private $defaultOptions = [
        'sanitize' => true,
        'highlight' => false,
    ];

    public function buildStringFromDataPath(String $str, $data=[], array $dataPaths=[], array $options=[])
    {
        $options = array_merge($this->defaultOptions, $options);
        if (!empty($dataPaths)) {
            $extractedVars = [];
            foreach ($dataPaths as $i => $dataPath) {
                $varValue = '';
                if (is_array($dataPath)) {
                    $varValue = '';
                    if (!empty($dataPath['datapath'])) {
                        $varValue = Hash::get($data, $dataPath['datapath']);
                    } else if (!empty($dataPath['raw'])) {
                        $varValue = $dataPath['raw'];
                    } else if (!empty($dataPath['function'])) {
                        $varValue = $dataPath['function']($data, $dataPath);
                    }
                    // $extractedVars[] = $varValue;
                } else {
                    $varValue = Hash::get($data, $dataPath);
                }
                if (empty($dataPath['function'])) {
                    $varValue = $options['sanitize'] ? h($varValue) : $varValue;
                }
                $extractedVars[] = $varValue;
            }
            foreach ($extractedVars as $i => $value) {
                $value = $options['highlight'] ? "<span class=\"font-weight-light\">${value}</span>" : $value;
                $str = str_replace(
                    "{{{$i}}}",
                    $value,
                    $str
                );
            }
        }
        return $str;
    }
}
