<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;

require_once(APP . 'Model' . DS . 'Table' . DS . 'SettingProviders' . DS . 'UserSettingsProvider.php');
use App\Settings\SettingsProvider\UserSettingsProvider;

class UserSettingsTable extends AppTable
{
    public $BOOKMARK_SETTING_NAME = 'ui.bookmarks';

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('Timestamp');
        $this->belongsTo(
            'Users'
        );
        $this->setDisplayField('name');

        $this->SettingsProvider = new UserSettingsProvider();
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence(['name', 'user_id'], 'create')
            ->notEmptyString('name', __('Please fill this field'))
            ->notEmptyString('user_id', __('Please supply the user id to which this setting belongs to'));
        return $validator;
    }

    public function getSettingsFromProviderForUser($user_id, $full = false): array
    {
        $settingsTmp = $this->getSettingsForUser($user_id)->toArray();
        $settings = [];
        foreach ($settingsTmp as $setting) {
            $settings[$setting->name] = $setting->value;
        }
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

    public function getSettingsForUser($user_id)
    {
        return $this->find()->where([
            'user_id' => $user_id,
        ])->all();
    }

    public function getSettingByName($user, $name)
    {
        return $this->find()->where([
            'user_id' => $user->id,
            'name' => $name,
        ])->first();
    }

    public function createSetting($user, $name, $value)
    {
        $setting = $this->newEmptyEntity();
        $data = [
            'name' => $name,
            'value' => $value,
            'user_id' => $user->id,
        ];
        $setting = $this->patchEntity($setting, $data);
        $savedData = $this->save($setting);
        return $savedData;
    }

    public function editSetting($user, $name, $value)
    {
        $setting = $this->getSettingByName($user, $name);
        $setting = $this->patchEntity($setting, [
            'value' => $value
        ]);
        $savedData = $this->save($setting);
        return $savedData;
    }

    public function saveBookmark($user, $data)
    {
        $setting = $this->getSettingByName($user, $this->BOOKMARK_SETTING_NAME);
        $bookmarkData = [
            'label' => $data['bookmark_label'],
            'name' => $data['bookmark_name'],
            'url' => $data['bookmark_url'],
        ];
        if (is_null($setting)) { // setting not found, create it
            $bookmarksData = json_encode([$bookmarkData]);
            $result = $this->createSetting($user, $this->BOOKMARK_SETTING_NAME, $bookmarksData);
        } else {
            $bookmarksData = json_decode($setting->value);
            $bookmarksData[] = $bookmarkData;
            $bookmarksData = json_encode($bookmarksData);
            $result = $this->editSetting($user, $this->BOOKMARK_SETTING_NAME, $bookmarksData);
        }
        return $result;
    }

    public function deleteBookmark($user, $data)
    {
        $setting = $this->getSettingByName($user, $this->BOOKMARK_SETTING_NAME);
        $bookmarkData = [
            'name' => $data['bookmark_name'],
            'url' => $data['bookmark_url'],
        ];
        if (is_null($setting)) { // Can't delete something that doesn't exist
            return null;
        } else {
            $bookmarksData = json_decode($setting->value, true);
            foreach ($bookmarksData as $i => $savedBookmark) {
                if ($savedBookmark['name'] == $bookmarkData['name'] && $savedBookmark['url'] == $bookmarkData['url']) {
                    unset($bookmarksData[$i]);
                }
            }
            $bookmarksData = json_encode($bookmarksData);
            $result = $this->editSetting($user, $this->BOOKMARK_SETTING_NAME, $bookmarksData);
        }
        return $result;
    }

    /**
     * validURI - Ensure the provided URI can be safely put as a link
     *
     * @param String $uri
     * @return bool if the URI is safe to be put as a link
     */
    public function validURI(String $uri): bool
    {
        $parsed = parse_url($uri);
        $isLocalPath = empty($parsed['scheme']) && empty($parsed['domain']) && !empty($parsed['path']);
        $isValidURL = !empty($parsed['scheme']) && in_array($parsed['scheme'], ['http', 'https']) && filter_var($uri, FILTER_SANITIZE_URL);
        return $isLocalPath || $isValidURL;
    }
}
