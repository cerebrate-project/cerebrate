<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Core\Configure;

require_once(APP . 'Model' . DS . 'Table' . DS . 'SettingProviders' . DS . 'CerebrateSettingsProvider.php');
use App\Settings\SettingsProvider\CerebrateSettingsProvider;

class SettingsTable extends AppTable
{
    private static $FILENAME = 'cerebrate';
    private static $CONFIG_KEY = 'Cerebrate';
    
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable(false);
        $this->SettingsProvider = new CerebrateSettingsProvider();
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
        if (empty($errors) && !empty($setting['beforeSave'])) {
            $setting['value'] = $value ?? '';
            $beforeSaveResult = $this->SettingsProvider->evaluateFunctionForSetting($setting['beforeSave'], $setting);
            if ($beforeSaveResult !== true) {
                $errors[] = $beforeSaveResult;
            }
        }
        if (empty($errors)) {
            $saveResult = $this->saveSettingOnDisk($name, $value);
            if ($saveResult) {
                if (!empty($setting['afterSave'])) {
                    $this->SettingsProvider->evaluateFunctionForSetting($setting['afterSave'], $setting);
                }
            }
        }
        return $errors;
    }

    private function normaliseValue($value, $setting)
    {
        if ($setting['type'] == 'boolean') {
            return (bool) $value;
        }
        return $value;
    }

    private function readSettings()
    {
        return Configure::read()[$this::$CONFIG_KEY];
    }

    private function saveSettingOnDisk($name, $value)
    {
        $settings = $this->readSettings();
        $settings[$name] = $value;
        Configure::write($this::$CONFIG_KEY, $settings);
        Configure::dump($this::$FILENAME, 'default', [$this::$CONFIG_KEY]);
        return true;
    }
}
