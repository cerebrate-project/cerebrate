<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

class SettingsTable extends AppTable
{
    private static $FILENAME = 'cerebrate';
    private static $CONFIG_KEY = 'Cerebrate';
    
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable(false);
        $this->SettingsProvider = TableRegistry::getTableLocator()->get('SettingsProvider');
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
        if (!empty($setting['beforeSave'])) {
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
