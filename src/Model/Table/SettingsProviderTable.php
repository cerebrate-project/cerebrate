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
                $notices = array_merge_recursive($notices, $this->getNoticesFromSettingsConfiguration($value));
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
        if ($setting['type'] == 'select') {
            if (!empty($setting['options']) && is_callable($setting['options'])) {
                $setting['options'] = $setting['options']($this);
            }
        }
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
            $validationResult = true;
            if (!isset($setting['value'])) {
                $validationResult = $this->settingValidator->testEmptyBecomesDefault(null, $setting);
            } else if (isset($setting['test'])) {
                $setting['value'] = $setting['value'] ?? '';
                $validationResult = $this->evaluateFunctionForSetting($setting['test'], $setting);
            }
            if ($validationResult !== true) {
                $setting['severity'] = $setting['severity'] ?? 'warning';
                if (!in_array($setting['severity'], $this->severities)) {
                    $setting['severity'] = 'warning';
                }
                $setting['errorMessage'] = $validationResult;
            }
            $setting['error'] = $validationResult !== true ? true : false;
        }
        return $setting;
    }
    
    /**
     * evaluateFunctionForSetting - evaluate the provided function. If function could not be evaluated, its result is defaulted to true
     *
     * @param  mixed $fun
     * @param  array $setting
     * @return mixed
     */
    public function evaluateFunctionForSetting($fun, $setting)
    {
        $functionResult = true;
        if (is_callable($fun)) { // Validate with anonymous function
            $functionResult = $fun($setting['value'], $setting, new Validator());
        } else if (method_exists($this->settingValidator, $fun)) { // Validate with function defined in settingValidator class
            $functionResult = $this->settingValidator->{$fun}($setting['value'], $setting);
        } else {
            $validator = new Validator();
            if (method_exists($validator, $fun)) { // Validate with cake's validator function
                $validator->{$fun};
                $functionResult = $validator->validate($setting['value']);
            }
        }
        return $functionResult;
    }

    /**
     * Support up to 3 level:
     *      Application -> Network -> Proxy -> Proxy.URL
     * 
     * - Leave errorMessage empty to let the validator generate the error message
     * - Default severity level is `info` if a `default` value is provided otherwise it becomes `critical`
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
                            'default' => '',
                            'name' => 'UUID',
                            'severity' => 'critical',
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
                            'test' => function($value) {
                                return empty($value) ? __('Oh not! it\'s not valid!') : true;
                            },
                            'beforeSave' => function($value, $setting) {
                                if ($value != 'foo') {
                                    return 'value must be `foo`!';
                                }
                                return true;
                            },
                            'afterSave' => function($value, $setting) {
                            },
                            'type' => 'string'
                        ],
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
                        'sc2.antagonist' => [
                            'description' => 'The real bad guy',
                            'default' => 'Amon',
                            'name' => 'Antagonist',
                            'options' => function($settingsProviders) {
                                return [
                                    'Amon' => 'Amon',
                                    'Sarah Kerrigan' => 'Sarah Kerrigan',
                                    'Narud' => 'Narud',
                                ];
                            },
                            'severity' => 'warning',
                            'type' => 'select'
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
                ],
                'Network' => [
                    'Proxy' => [
                        'proxy.host' => [
                            'description' => __('The hostname of an HTTP proxy for outgoing sync requests. Leave empty to not use a proxy.'),
                            'name' => __('Host'),
                            'test' => 'testHostname',
                            'type' => 'string',
                        ],
                        'proxy.port' => [
                            'description' => __('The TCP port for the HTTP proxy.'),
                            'name' => __('Port'),
                            'test' => 'testForRangeXY',
                            'type' => 'integer',
                        ],
                        'proxy.user' => [
                            'description' => __('The authentication username for the HTTP proxy.'),
                            'default' => 'admin',
                            'name' => __('User'),
                            'dependsOn' => 'proxy.host',
                            'type' => 'string',
                        ],
                        'proxy.password' => [
                            'description' => __('The authentication password for the HTTP proxy.'),
                            'default' => '',
                            'name' => __('Password'),
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
                'Development' => [
                    'Debugging' => [
                        'app.security.debug' => [
                            'description' => __('The debug level of the instance'),
                            'default' => 0,
                            'name' => __('Debug Level'),
                            'test' => function($value, $setting, $validator) {
                                $validator->range('value', [0, 3]);
                                return testValidator($value, $validator);
                            },
                            'type' => 'select',
                            'options' => [
                                0 => __('Debug Off'),
                                1 => __('Debug On'),
                                2 => __('Debug On + SQL Dump'),
                            ]
                        ],
                    ],
                ]
            ],
            'Features' => [
            ],
        ];
    }
}

function testValidator($value, $validator)
{
    $errors = $validator->validate(['value' => $value]);
    return !empty($errors) ? implode(', ', $errors['value']) : true;
}

class SettingValidator
{

    public function testEmptyBecomesDefault($value, &$setting)
    {
        if (!empty($value)) {
            return true;
        } else if (!empty($setting['default'])) {
            $setting['severity'] = $setting['severity'] ?? 'info';
            return __('Setting is not set, fallback to default value: {0}', $setting['default']);
        } else {
            $setting['severity'] = $setting['severity'] ?? 'critical';
            return __('Cannot be empty. Setting does not have a default value.');
        }
    }

    public function testForEmpty($value, &$setting)
    {
        return !empty($value) ? true : __('Cannot be empty');
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

    public function testUuid($value, &$setting) {
        if (empty($value) || !preg_match('/^\{?[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}\}?$/', $value)) {
            return __('Invalid UUID.');
        }
        return true;
    }
}