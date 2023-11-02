<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;

class SGOsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('sgo');
        parent::initialize($config);
        $this->belongsTo('SharingGroups');
        $this->belongsTo('Organisations');
    }

    public function attach(int $sg_id, int $org_id, array $additional_data = []): bool
    {
        $sgo = $this->find()->where([
            'sharing_group_id' => $sg_id,
            'organisation_id' => $org_id
        ])->first();
        if (empty($sgo)) {
            $sgo = $this->newEmptyEntity();
            $sgo->sharing_group_id = $sg_id;
            $sgo->organisation_id = $org_id;
        }
        $sgo->extend = empty($additional_data['extend']) ? 0 : 1;
        if ($this->save($sgo)) {
            return true;
        }
        return false;

    }

    public function detach(): bool
    {
        $sgo = $this->find()->where([
            'sharing_group_id' => $sg_id,
            'organisation_id' => $org_id
        ])->first();
        if (!empty($sgo)) {
            if (!$this->delete($sgo)) {
                return false;
            }
        }
        return true;
    }
}
