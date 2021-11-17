<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;

class SharingGroupsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->addBehavior('Timestamp');
        $this->addBehavior('AuditLog');
        $this->belongsTo(
            'Users'
        );
        $this->belongsTo(
            'Organisations'
        );
        $this->belongsToMany(
            'SharingGroupOrgs',
            [
                'className' => 'Organisations',
                'foreignKey' => 'sharing_group_id',
                'joinTable' => 'sgo',
                'targetForeignKey' => 'organisation_id'
            ]
        );
        $this->setDisplayField('name');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence(['name', 'releasability'], 'create');
        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        return $rules;
    }

    public function captureSharingGroup($input, int $user_id = 0): ?int
    {
        if (!empty($input['uuid'])) {
            $existingSG = $this->find()->where([
                'uuid' => $input['uuid']
            ])->first();
        } else {
            return null;
        }
        if (empty($existingSG)) {
            $entityToSave = $this->newEmptyEntity();
            $input['organisation_id'] = $this->Organisations->captureOrg($input['organisation']);
            $input['user_id'] = $user_id;
            $this->patchEntity($entityToSave, $input, [
                'accessibleFields' => $entityToSave->getAccessibleFieldForNew()
            ]);
        } else {
            $this->patchEntity($existingSG, $input);
            $entityToSave = $existingSG;
        }
        $savedEntity = $this->save($entityToSave, ['associated' => false]);
        if (!$savedEntity) {
            return null;
        }
        $this->postCaptureActions($savedEntity, $input);
        return $savedEntity->id;
    }

    public function postCaptureActions($savedEntity, $input): void
    {
        $orgs = [];
        foreach ($input['sharing_group_orgs'] as $sgo) {
            $organisation_id = $this->Organisations->captureOrg($sgo);
            $orgs[] = $this->SharingGroupOrgs->get($organisation_id);
        }
        $this->SharingGroupOrgs->link($savedEntity, $orgs);
    }
}
