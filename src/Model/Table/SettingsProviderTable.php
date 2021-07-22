<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SettingsProviderTable extends AppTable
{
    private $settingsConfiguration = [];
    private $error_critical = '',
            $error_warning = '',
            $error_info = '';
    private $severities = ['info', 'warning', 'critical'];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->settingsConfiguration = $this->generateSettingsConfiguration();
        $this->setTable(false);
        $this->error_critical =  __('Cerebrate will not operate correctly or will be unsecure until these issues are resolved.');
        $this->error_warning =  __('Some of the features of Cerebrate cannot be utilised until these issues are resolved.');
        $this->error_info =  __('There are some optional tweaks that could be done to improve the looks of your Cerebrate instance.');
        $this->settingValidator = new SettingValidator();
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
    
    /**
     * mergeSettingsIntoSettingConfiguration Inject the provided settings into the configuration while performing depencency and validation checks
     *
     * @param  array $settingConf the setting configuration to have the setting injected into
     * @param  array $settings the settings
     * @return void
     */
    private function mergeSettingsIntoSettingConfiguration(array $settingConf, array $settings, string $path=''): array
    {
        foreach ($settingConf as $key => $value) {
            if ($this->isLeaf($value)) {
                if (isset($settings[$key])) {
                    $settingConf[$key]['value'] = $settings[$key];
                }
                if (empty($settingConf[$key]['severity'])) {
                    $settingConf[$key]['severity'] = 'warning';
                }
                $settingConf[$key] = $this->evaluateLeaf($settingConf[$key], $settingConf);
                $settingConf[$key]['setting-path'] = $path;
                $settingConf[$key]['true-name'] = $key;
            } else {
                $currentPath = empty($path) ? $key : sprintf('%s.%s', $path, $key);
                $settingConf[$key] = $this->mergeSettingsIntoSettingConfiguration($value, $settings, $currentPath);
            }
        }
        return $settingConf;
    }

    public function flattenSettingsConfiguration(array $settingsProvider, $flattenedSettings=[]): array
    {
        foreach ($settingsProvider as $key => $value) {
            if ($this->isLeaf($value)) {
                $flattenedSettings[$key] = $value;
            } else {
                $flattenedSettings = $this->flattenSettingsConfiguration($value, $flattenedSettings);
            }
        }
        return $flattenedSettings;
    }
    
    /**
     * getNoticesFromSettingsConfiguration Summarize the validation errors
     *
     * @param  array $settingsProvider the setting configuration having setting value assigned
     * @return void
     */
    public function getNoticesFromSettingsConfiguration(array $settingsProvider): array
    {
        $notices = [];
        foreach ($settingsProvider as $key => $value) {
            if ($this->isLeaf($value)) {
                if (!empty($value['error'])) {
                    if (empty($notices[$value['severity']])) {
                        $notices[$value['severity']] = [];
                    }
                    $notices[$value['severity']][] = $key;
                }
            } else {
                $notices = array_merge($notices, $this->getNoticesFromSettingsConfiguration($value));
            }
        }
        return $notices;
    }

    private function isLeaf($setting)
    {
        return !empty($setting['name']) && !empty($setting['type']);
    }

    private function evaluateLeaf($setting, $settingSection)
    {
        $skipValidation = false;
        if (isset($setting['dependsOn'])) {
            $parentSetting = null;
            foreach ($settingSection as $settingSectionName => $settingSectionConfig) {
                if ($settingSectionName == $setting['dependsOn']) {
                    $parentSetting = $settingSectionConfig;
                }
            }
            if (!is_null($parentSetting)) {
                $parentSetting = $this->evaluateLeaf($parentSetting, $settingSection);
                $skipValidation = $parentSetting['error'] === true || empty($parentSetting['value']);
            }
        }
        if (!$skipValidation) {
            if (isset($setting['test'])) {
                $error = false;
                $setting['value'] = $setting['value'] ?? '';
                if (is_callable($setting['test'])) { // Validate with anonymous function
                    $error = $setting['test']($setting['value'], $setting);
                } else if (method_exists($this->settingValidator, $setting['test'])) { // Validate with function defined in settingValidator class
                    $error = $this->settingValidator->{$setting['test']}($setting['value'], $setting);
                } else {
                    $validator = new Validator();
                    if (method_exists($validator, $setting['test'])) { // Validate with cake's validator function
                        $validator->{$setting['test']};
                        $error = $validator->validate($setting['value']);
                    }
                }
                if ($error !== true) {
                    $setting['severity'] = $setting['severity'] ?? 'warning';
                    if (!in_array($setting['severity'], $this->severities)) {
                        $setting['severity'] = 'warning';
                    }
                    $setting['errorMessage'] = $error;
                }
                $setting['error'] = $error !== false ? true : false;
            }
        }
        return $setting;
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
                        'app.baseurl' => [
                            'description' => __('The base url of the application (in the format https://www.mymispinstance.com or https://myserver.com/misp). Several features depend on this setting being correctly set to function.'),
                            'severity' => 'critical',
                            'default' => '',
                            'name' => __('Base URL'),
                            'test' => 'testBaseURL',
                            'type' => 'string',
                        ],
                        'app.uuid' => [
                            'description' => __('The Cerebrate instance UUID. This UUID is used to identify this instance.'),
                            'severity' => 'critical',
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
                            'default' => 'A-default-value',
                            'name' => 'To DEL',
                            'test' => function ($value) {
                                return empty($value) ? __('Oh not! it\'s not valid!') : '';
                            },
                            'type' => 'string'
                        ],
                        'to-del2' => [
                            'description' => 'to del',
                            'errorMessage' => 'to del',
                            'default' => '',
                            'name' => 'To DEL 2',
                            'type' => 'string'
                        ],
                        'to-del3' => [
                            'description' => 'to del',
                            'errorMessage' => 'to del',
                            'default' => '',
                            'name' => 'To DEL 2',
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
                        'proxy.host' => [
                            'description' => __('The hostname of an HTTP proxy for outgoing sync requests. Leave empty to not use a proxy.'),
                            'default' => '',
                            'name' => __('Host'),
                            'test' => 'testHostname',
                            'type' => 'string',
                        ],
                        'proxy.port' => [
                            'description' => __('The TCP port for the HTTP proxy.'),
                            'default' => '',
                            'name' => __('Port'),
                            'test' => 'testForRangeXY',
                            'type' => 'integer',
                        ],
                        'proxy.user' => [
                            'description' => __('The authentication username for the HTTP proxy.'),
                            'default' => 'admin',
                            'name' => __('User'),
                            'test' => 'testEmptyBecomesDefault',
                            'dependsOn' => 'proxy.host',
                            'type' => 'string',
                        ],
                        'proxy.password' => [
                            'description' => __('The authentication password for the HTTP proxy.'),
                            'default' => '',
                            'name' => __('Password'),
                            'test' => 'testEmptyBecomesDefault',
                            'dependsOn' => 'proxy.host',
                            'type' => 'string',
                        ],
                    ],
                ],
                'UI' => [
                    'General' => [
                        'app.ui.dark' => [
                            'description' => __('Enable the dark theme of the application'),
                            'default' => false,
                            'name' => __('Dark theme'),
                            'test' => function() {
                                return 'Fake error';
                            },
                            'type' => 'boolean',
                        ],
                    ],
                ],
            ],
            'Security' => [
                'Network' => [
                    'Proxy Test' => [
                        'proxy-test.host' => [
                            'description' => __('The hostname of an HTTP proxy for outgoing sync requests. Leave empty to not use a proxy.'),
                            'default' => '',
                            'name' => __('Host'),
                            'test' => 'testHostname',
                            'type' => 'string',
                        ],
                        'proxy-test.port' => [
                            'description' => __('The TCP port for the HTTP proxy.'),
                            'default' => '',
                            'name' => __('Port'),
                            'test' => 'testForRangeXY',
                            'type' => 'integer',
                        ],
                        'proxy-test.user' => [
                            'description' => __('The authentication username for the HTTP proxy.'),
                            'default' => '',
                            'dependsOn' => 'host',
                            'name' => __('User'),
                            'test' => 'testEmptyBecomesDefault',
                            'type' => 'string',
                        ],
                        'proxy-test.password' => [
                            'description' => __('The authentication password for the HTTP proxy.'),
                            'default' => '',
                            'dependsOn' => 'host',
                            'name' => __('Password'),
                            'test' => 'testEmptyBecomesDefault',
                            'type' => 'string',
                        ],
                    ],
                ]
            ],
            'Features' => [
            ],
        ];
    }
}

class SettingValidator
{

    public function testEmptyBecomesDefault($value, $setting)
    {
        if (!empty($value)) {
            return true;
        } else if (!empty($setting['default'])) {
            return __('Setting is not set, fallback to default value: {0}', $setting['default']);
        } else {
            return __('Cannot be empty');
        }
    }

    public function testForEmpty($value, $setting)
    {
        return !empty($value) ? true : __('Cannot be empty');
    }

    public function testBaseURL($value, $setting)
    {
        if (empty($value)) {
            return __('Cannot be empty');
        }
        if (!empty($value) && !preg_match('/^http(s)?:\/\//i', $value)) {
            return __('Invalid URL, please make sure that the protocol is set.');
        }
        return true;
    }

    public function testUuid($value, $setting) {
        if (empty($value) || !preg_match('/^\{?[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}\}?$/', $value)) {
            return __('Invalid UUID.');
        }
        return true;
    }
}