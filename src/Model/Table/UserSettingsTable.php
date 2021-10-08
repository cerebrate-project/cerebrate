<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class UserSettingsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('Timestamp');
        $this->belongsTo(
            'Users'
        );
        $this->setDisplayField('name');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence(['name', 'user_id'], 'create')
            ->notEmptyString('name', __('Please fill this field'))
            ->notEmptyString('user_id', __('Please supply the user id to which this setting belongs to'));
        return $validator;
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
