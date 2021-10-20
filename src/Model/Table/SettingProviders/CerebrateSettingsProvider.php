<?php

namespace App\Settings\SettingsProvider;

use Cake\ORM\TableRegistry;

require_once(APP . 'Model' . DS . 'Table' . DS . 'SettingProviders' . DS . 'BaseSettingsProvider.php');

use App\Settings\SettingsProvider\BaseSettingsProvider;
use App\Settings\SettingsProvider\SettingValidator;

class CerebrateSettingsProvider extends BaseSettingsProvider
{

    public function __construct()
    {
        $this->settingValidator = new CerebrateSettingValidator();
        parent::__construct();
    }

    protected function generateSettingsConfiguration()
    {
        return [
            'Application' => [
                'General' => [
                    'Essentials' => [
                        '_description' => __('Ensentials settings required for the application to run normally.'),
                        '_icon' => 'user-cog',
                        'app.baseurl' => [
                            'name' => __('Base URL'),
                            'type' => 'string',
                            'description' => __('The base url of the application (in the format https://www.mymispinstance.com or https://myserver.com/misp). Several features depend on this setting being correctly set to function.'),
                            'default' => '',
                            'severity' => 'critical',
                            'test' => 'testBaseURL',
                        ],
                        'app.uuid' => [
                            'name' => 'UUID',
                            'type' => 'string',
                            'description' => __('The Cerebrate instance UUID. This UUID is used to identify this instance.'),
                            'default' => '',
                            'severity' => 'critical',
                            'test' => 'testUuid',
                        ],
                    ],
                    /*
                    'Miscellaneous' => [
                        'sc2.hero' => [
                            'description' => 'The true hero',
                            'default' => 'Sarah Kerrigan',
                            'name' => 'Hero',
                            'options' => [
                                'Jim Raynor' => 'Jim Raynor',
                                'Sarah Kerrigan' => 'Sarah Kerrigan',
                                'Artanis' => 'Artanis',
                                'Zeratul' => 'Zeratul',
                            ],
                            'type' => 'select'
                        ],
                        'sc2.antagonists' => [
                            'description' => 'The bad guys',
                            'default' => 'Amon',
                            'name' => 'Antagonists',
                            'options' => function ($settingsProviders) {
                                return [
                                    'Amon' => 'Amon',
                                    'Sarah Kerrigan' => 'Sarah Kerrigan',
                                    'Narud' => 'Narud',
                                ];
                            },
                            'severity' => 'warning',
                            'type' => 'multi-select'
                        ],
                    ],
                    'floating-setting' => [
                        'description' => 'floaringSetting',
                        // 'default' => 'A default value',
                        'name' => 'Uncategorized Setting',
                        // 'severity' => 'critical',
                        'severity' => 'warning',
                        // 'severity' => 'info',
                        'type' => 'integer'
                    ],
                    */
                ],
                'Network' => [
                    'Proxy' => [
                        'proxy.host' => [
                            'name' => __('Host'),
                            'type' => 'string',
                            'description' => __('The hostname of an HTTP proxy for outgoing sync requests. Leave empty to not use a proxy.'),
                            'test' => 'testHostname',
                        ],
                        'proxy.port' => [
                            'name' => __('Port'),
                            'type' => 'integer',
                            'description' => __('The TCP port for the HTTP proxy.'),
                            'test' => 'testForRangeXY',
                        ],
                        'proxy.user' => [
                            'name' => __('User'),
                            'type' => 'string',
                            'description' => __('The authentication username for the HTTP proxy.'),
                            'default' => 'admin',
                            'dependsOn' => 'proxy.host',
                        ],
                        'proxy.password' => [
                            'name' => __('Password'),
                            'type' => 'string',
                            'description' => __('The authentication password for the HTTP proxy.'),
                            'default' => '',
                            'dependsOn' => 'proxy.host',
                        ],
                    ],
                ],
                'UI' => [
                    'General' => [
                        'ui.bsTheme' => [
                            'description' => 'The Bootstrap theme to use for the application',
                            'default' => 'default',
                            'name' => 'UI Theme',
                            'options' => function ($settingsProviders) {
                                $instanceTable = TableRegistry::getTableLocator()->get('Instance');
                                $themes = $instanceTable->getAvailableThemes();
                                return array_combine($themes, $themes);
                            },
                            'severity' => 'info',
                            'type' => 'select'
                        ],
                    ],
                ],
            ],
            'Security' => [
                'Development' => [
                    'Debugging' => [
                        'security.debug' => [
                            'name' => __('Debug Level'),
                            'type' => 'select',
                            'description' => __('The debug level of the instance'),
                            'default' => 0,
                            'options' => [
                                0 => __('Debug Off'),
                                1 => __('Debug On'),
                                2 => __('Debug On + SQL Dump'),
                            ],
                            'test' => function ($value, $setting, $validator) {
                                $validator->range('value', [0, 3]);
                                return testValidator($value, $validator);
                            },
                        ],
                    ],
                ]
            ],
            'Features' => [
                'Demo Settings' => [
                    'demo.switch' => [
                        'name' => __('Switch'),
                        'type' => 'boolean',
                        'description' => __('A switch acting as a checkbox'),
                        'default' => false,
                        'test' => function () {
                            return 'Fake error';
                        },
                    ],
                ]
            ],
        ];
    }
}

function testValidator($value, $validator)
{
    $errors = $validator->validate(['value' => $value]);
    return !empty($errors) ? implode(', ', $errors['value']) : true;
}

class CerebrateSettingValidator extends SettingValidator
{
    public function testUuid($value, &$setting)
    {
        if (empty($value) || !preg_match('/^\{?[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}\}?$/', $value)) {
            return __('Invalid UUID.');
        }
        return true;
    }


    public function testBaseURL($value, &$setting)
    {
        if (empty($value)) {
            return __('Cannot be empty');
        }
        if (!empty($value) && !preg_match('/^http(s)?:\/\//i', $value)) {
            return __('Invalid URL, please make sure that the protocol is set.');
        }
        return true;
    }
}
