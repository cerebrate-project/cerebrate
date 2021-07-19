<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SettingsProviderTable extends AppTable
{
    private $settingsConfiguration = [];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->settingsConfiguration = $this->generateSettingsConfiguration();
        $this->setTable(false);
    }
    
    /**
     * getSettingsConfiguration Return the setting configuration and merge existing settings into it if provided
     *
     * @param  null|array $settings - Settings to be merged in the provided setting configuration
     * @return array
     */
    public function getSettingsConfiguration($settings = null) {
        $settingConf = $this->settingsConfiguration;
        if (!is_null($settings)) {
            $settingConf = $this->mergeSettingsIntoSettingConfiguration($settingConf, $settings);
        }
        return $settingConf;
    }

    private function mergeSettingsIntoSettingConfiguration($settingConf, $settings)
    {
        foreach ($settingConf as $key => $value) {
            if ($this->isLeaf($value)) {
                if (isset($settings[$key])) {
                    $settingConf[$key]['value'] = $settings[$key];
                }
            } else {
                $settingConf[$key] = $this->mergeSettingsIntoSettingConfiguration($value, $settings);
            }
        }
        return $settingConf;
    }

    private function isLeaf($setting)
    {
        return !empty($setting['name']) && !empty($setting['type']);
    }

    /**
     * Support up to 3 level:
     *      Application -> Network -> Proxy -> Proxy.URL
     * 
     * Leave errorMessage empty to let the validator generate the error message
     */
    private function generateSettingsConfiguration()
    {
        return [
            'Application' => [
                'General' => [
                    'Essentials' => [
                        'baseurl' => [
                            'description' => __('The base url of the application (in the format https://www.mymispinstance.com or https://myserver.com/misp). Several features depend on this setting being correctly set to function.'),
                            'errorMessage' => __('The currently set baseurl does not match the URL through which you have accessed the page. Disregard this if you are accessing the page via an alternate URL (for example via IP address).'),
                            'default' => '',
                            'name' => __('Base URL'),
                            'test' => 'testBaseURL',
                            'type' => 'string',
                        ],
                        'uuid' => [
                            'description' => __('The Cerebrate instance UUID. This UUID is used to identify this instance.'),
                            'errorMessage' => __('No valid UUID set'),
                            'default' => '',
                            'name' => 'UUID',
                            'test' => 'testUuid',
                            'type' => 'string'
                        ],
                    ],
                    'Miscellaneous' => [
                        'to-del' => [
                            'description' => 'to del',
                            'errorMessage' => 'to del',
                            'default' => '',
                            'name' => 'To DEL',
                            'type' => 'string'
                        ],
                        'to-del2' => [
                            'description' => 'to del',
                            'errorMessage' => 'to del',
                            'default' => '',
                            'name' => 'To DEL',
                            'type' => 'string'
                        ],
                        'to-del3' => [
                            'description' => 'to del',
                            'errorMessage' => 'to del',
                            'default' => '',
                            'name' => 'To DEL',
                            'type' => 'string'
                        ],
                    ],
                    'floating-setting' => [
                        'description' => 'floaringSetting',
                        'errorMessage' => 'floaringSetting',
                        'default' => '',
                        'name' => 'Uncategorized Setting',
                        'type' => 'string'
                    ],
                ],
                'Network' => [
                    'Proxy' => [
                        'host' => [
                            'description' => __('The hostname of an HTTP proxy for outgoing sync requests. Leave empty to not use a proxy.'),
                            'default' => '',
                            'name' => __('Host'),
                            'test' => 'testHostname',
                            'type' => 'string',
                        ],
                        'port' => [
                            'description' => __('The TCP port for the HTTP proxy.'),
                            'default' => '',
                            'name' => __('Port'),
                            'test' => 'testForRangeXY',
                            'type' => 'integer',
                        ],
                        'user' => [
                            'description' => __('The authentication username for the HTTP proxy.'),
                            'default' => '',
                            'name' => __('User'),
                            'test' => 'testForEmpty',
                            'type' => 'string',
                        ],
                        'password' => [
                            'description' => __('The authentication password for the HTTP proxy.'),
                            'default' => '',
                            'name' => __('Password'),
                            'test' => 'testForEmpty',
                            'type' => 'string',
                        ],
                    ],
                    'Proxy2' => [
                        'host' => [
                            'description' => __('The hostname of an HTTP proxy for outgoing sync requests. Leave empty to not use a proxy.'),
                            'default' => '',
                            'name' => __('Host'),
                            'test' => 'testHostname',
                            'type' => 'string',
                        ],
                        'port' => [
                            'description' => __('The TCP port for the HTTP proxy.'),
                            'default' => '',
                            'name' => __('Port'),
                            'test' => 'testForRangeXY',
                            'type' => 'integer',
                        ],
                        'user' => [
                            'description' => __('The authentication username for the HTTP proxy.'),
                            'default' => '',
                            'name' => __('User'),
                            'test' => 'testForEmpty',
                            'type' => 'string',
                        ],
                        'password' => [
                            'description' => __('The authentication password for the HTTP proxy.'),
                            'default' => '',
                            'name' => __('Password'),
                            'test' => 'testForEmpty',
                            'type' => 'string',
                        ],
                    ],
                ],
                'UI' => [
                    'dark' => [
                        'description' => __('Enable the dark theme of the application'),
                        'default' => false,
                        'name' => __('Dark theme'),
                        'type' => 'boolean',
                    ],
                ],
            ],
            'Features' => [
            ],
            'Security' => [
            ],
        ];
    }
}

