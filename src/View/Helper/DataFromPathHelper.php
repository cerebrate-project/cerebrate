<?php

namespace App\View\Helper;

use Cake\View\Helper;
use Cake\Utility\Hash;

class DataFromPathHelper extends Helper
{
    private $defaultOptions = [
        'sanitize' => true, // Should the variables to be injected into the string be sanitized. (ignored with the function)
        'highlight' => false, // Should the extracted data be highlighted
    ];
    
    /**
     * buildStringFromDataPath Inject data into a string at the correct place
     *
     * @param  String $str The string that will have its arguements replaced by their value
     * @param  mixed  $data The data from which the value of datapath arguement will be taken
     * @param  array  $strArgs The arguments to be injected into the string.
     *      - Each argument ca be of mixed type:
     *          - String: A cakephp's Hash datapath used to extract the data
     *          - array:  can contain a key of either
     *              - `datapath`: A cakephp's Hash datapath used to extract the data
     *              - `raw`: A raw string to be injecte as-is
     *              - `function`: A function to be executed with its $strArgs being passed
     * @param  array  $options Allows to configure the behavior of the function
     * @return String The string with its arguments replaced by their value
     */
    public function buildStringFromDataPath(String $str, $data=[], array $strArgs=[], array $options=[])
    {
        $options = array_merge($this->defaultOptions, $options);
        if (!empty($strArgs)) {
            $extractedVars = [];
            foreach ($strArgs as $i => $strArg) {
                $varValue = '';
                if (is_array($strArg)) {
                    $varValue = '';
                    if (!empty($strArg['datapath'])) {
                        $varValue = Hash::get($data, $strArg['datapath']);
                    } else if (!empty($strArg['raw'])) {
                        $varValue = $strArg['raw'];
                    } else if (!empty($strArg['function'])) {
                        $varValue = $strArg['function']($data, $strArg);
                    }
                } else {
                    $varValue = Hash::get($data, $strArg);
                }
                if (empty($strArg['function'])) {
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
    
    /**
     * buildStringsInArray
     *
     * @param  array $stringArray The array containing the strings that will have their arguments replaced by their value
     * @param  mixed $data The data from which the value of datapath arguement will be taken
     * @param  array $instructions Instruct how $stringArray should be processed
     *      - Keys are the path to the string
     *      - Values are the path to the argument
     * @param  array $options Allows to configure the behavior of the function
     * @return array The array containing the strings with their arguments replaced by their value
     */
    public function buildStringsInArray(array $stringArray, $data=[], array $instructions, array $options=[])
    {
        foreach ($instructions as $stringPath => $argsPath) {
            $theString = Hash::get($stringArray, $stringPath);
            if (!is_null($theString)) {
                $theArgs = Hash::get($stringArray, $argsPath);
                $theArgs = is_null($theArgs) ? [] : $theArgs;
                $theArgs = !is_array($theArgs) ? [$theArgs] : $theArgs;
                if (!empty($theArgs['function'])) {
                    $newString = $theArgs['function']($data, $theArgs);
                } else {
                    $newString = $this->buildStringFromDataPath($theString, $data, $theArgs, $options);
                }
                $stringArray = Hash::insert($stringArray, $stringPath, $newString);
            }
        }
        return $stringArray;
    }
}
