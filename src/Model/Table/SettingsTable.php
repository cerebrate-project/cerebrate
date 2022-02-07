<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Filesystem\File;
use Cake\Core\Configure;
use Cake\Error\Debugger;

require_once(APP . 'Model' . DS . 'Table' . DS . 'SettingProviders' . DS . 'CerebrateSettingsProvider.php');
use App\Settings\SettingsProvider\CerebrateSettingsProvider;

class SettingsTable extends AppTable
{
    private static $FILENAME = 'cerebrate';
    private static $CONFIG_KEY = 'Cerebrate';
    private static $DUMPABLE = [
        'Cerebrate',
        'proxy',
        'ui',
        'keycloak',
        'app'
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable(false);
        $this->SettingsProvider = new CerebrateSettingsProvider();
        $this->addBehavior('AuditLog');
    }

    public function getSettings($full=false): array
    {
        $settings = $this->readSettings();
        if (empty($full)) {
            return $settings;
        } else {
            $settingsProvider = $this->SettingsProvider->getSettingsConfiguration($settings);
            $settingsFlattened = $this->SettingsProvider->flattenSettingsConfiguration($settingsProvider);
            $notices = $this->SettingsProvider->getNoticesFromSettingsConfiguration($settingsProvider, $settings);
            return [
                'settings' => $settings,
                'settingsProvider' => $settingsProvider,
                'settingsFlattened' => $settingsFlattened,
                'notices' => $notices,
            ];
        }
    }

    public function getSetting($name=false): array
    {
        $settings = $this->readSettings();
        $settingsProvider = $this->SettingsProvider->getSettingsConfiguration($settings);
        $settingsFlattened = $this->SettingsProvider->flattenSettingsConfiguration($settingsProvider);
        return $settingsFlattened[$name] ?? [];
    }

    public function saveSetting(string $name, string $value): array
    {
        $errors = [];
        $setting = $this->getSetting($name);
        $value = $this->normaliseValue($value, $setting);
        if ($setting['type'] == 'select') {
            if (!in_array($value, array_keys($setting['options']))) {
                $errors[] = __('Invalid option provided');
            }
        }
        if ($setting['type'] == 'multi-select') {
            foreach ($value as $v) {
                if (!in_array($v, array_keys($setting['options']))) {
                    $errors[] = __('Invalid option provided');
                }
            }
        }
        $setting['value'] = $value ?? '';
        if (isset($setting['test'])) {
            $validationResult = $this->SettingsProvider->evaluateFunctionForSetting($setting['test'], $setting);
            if ($validationResult !== true) {
                $errors[] = $validationResult;
                $setting['errorMessage'] = $validationResult;
            }
        }
        if (empty($errors) && !empty($setting['beforeSave'])) {
            $beforeSaveResult = $this->SettingsProvider->evaluateFunctionForSetting($setting['beforeSave'], $setting);
            if ($beforeSaveResult !== true) {
                $errors[] = $beforeSaveResult;
                $setting['errorMessage'] = $validationResult;
            }
        }
        if (empty($errors)) {
            $saveResult = $this->saveSettingOnDisk($name, $setting['value']);
            if ($saveResult) {
                if (!empty($setting['afterSave'])) {
                    $this->SettingsProvider->evaluateFunctionForSetting($setting['afterSave'], $setting);
                }
            } else {
                $errors[] = __('Could not save settings on disk');
            }
        }
        return $errors;
    }

    private function normaliseValue($value, $setting)
    {
        if ($setting['type'] == 'boolean') {
            return (bool) $value;
        }
        if ($setting['type'] == 'multi-select') {
            if (!is_array($value)) {
                $value = json_decode($value);
            }
        }
        return $value;
    }

    private function readSettings()
    {
        $settingPaths = $this->SettingsProvider->retrieveSettingPathsBasedOnBlueprint();
        $settings = [];
        foreach ($settingPaths as $path) {
            if (Configure::check($path)) {
                $settings[$path] = Configure::read($path);
            }
        }
        return $settings;
    }

    private function loadSettings(): void
    {
        $settings = file_get_contents(CONFIG . 'config.json');
        $settings = json_decode($settings, true);
        foreach ($settings as $path => $setting) {
            Configure::write($path, $setting);
        }
    }

    private function saveSettingOnDisk($name, $value)
    {
        $settings = $this->readSettings();
        $settings[$name] = $value;
        $settings = json_encode($settings, JSON_PRETTY_PRINT);
        $path = CONFIG . 'config.json';
        $file = new File($path);
        if ($file->writable()) {
            $success = file_put_contents($path, $settings);
            if ($success) {
                $this->loadSettings();
            }
        } else {
            $success = false;
        }
        return $success;
    }
}
