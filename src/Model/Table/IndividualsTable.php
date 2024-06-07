<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Utility\Hash;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\Core\Configure;


class IndividualsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('UUID');
        $this->addBehavior('Timestamp');
        $this->addBehavior('Tags.Tag');
        $this->addBehavior('MetaFields');
        $this->addBehavior('AuditLog');

        $this->hasMany(
            'Alignments',
            [
                'dependent' => true,
                'cascadeCallbacks' => true
            ]
        );
        $this->hasMany(
            'EncryptionKeys',
            [
                'foreignKey' => 'owner_id',
                'conditions' => ['owner_model' => 'individual']
            ]
        );
        $this->hasOne(
            'Users'
        );
        $this->belongsToMany('Organisations', [
            'through' => 'Alignments',
        ]);
        $this->belongsToMany('MailingLists', [
            'through' => 'mailing_lists_individuals',
        ]);

        $this->setDisplayField('email');
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['email']));
        return $rules;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->email('email')
            ->requirePresence(['email'], 'create');
        return $validator;
    }

    public function captureIndividual($individual, $skipUpdate = false): ?int
    {
        if (!empty($individual['uuid'])) {
            $existingIndividual = $this->find()->where([
                'uuid' => $individual['uuid']
            ])->first();
        } else {
            $existingIndividual = $this->find()->where([
                'email' => $individual['email']
            ])->first();
        }
        if (empty($existingIndividual)) {
            $entityToSave = $this->newEmptyEntity();
            $this->patchEntity($entityToSave, $individual, [
                'accessibleFields' => $entityToSave->getAccessibleFieldForNew()
            ]);
        } else {
            if ($skipUpdate) {
                return $existingIndividual->id;
            }
            $this->patchEntity($existingIndividual, $individual);
            $entityToSave = $existingIndividual;
        }
        $entityToSave->setDirty('modified', false);
        $savedEntity = $this->save($entityToSave, ['associated' => false]);
        if (!$savedEntity) {
            return null;
        }
        $this->postCaptureActions($savedEntity);
        return $savedEntity->id;
    }

    public function postCaptureActions($individual): void
    {
        if (!empty($individual['metaFields'])) {
            $this->saveMetaFields($id, $individual);
        }
        if (!empty($individual['alignments'])) {
            $Organisation = \Cake\ORM\TableRegistry::getTableLocator()->get('Organisations');
            foreach ($individual['alignments'] as $alignment) {
                $org_id = $Organisation->captureOrg($alignment['organisation']);
                if ($org_id) {
                    $this->Alignments->setAlignment($org_id, $individual->id, $alignment['type']);
                }
            }
        }
    }

    public function findAligned(Query $query, array $options)
    {
        $query = $query->select(['Individuals.id']);
        if (empty($options['organisation_id'])) {
            $query->leftJoinWith('Alignments')->where(['Alignments.organisation_id IS' => null]);
        } else {
            $query->innerJoinWith('Alignments')
                ->where(['Alignments.organisation_id IN' => $options['organisation_id']]);
        }
        return $query->group(['Individuals.id', 'Individuals.uuid']);
    }

    public function getValidIndividualsToEdit(object $currentUser): array
    {
        $isSiteAdmin = $currentUser['role']['perm_admin'];
        $isGroupAdmin = $currentUser['role']['perm_group_admin'];
        $validRoles = $this->Users->Roles->find('list')->select(['id']);
        if (!$isSiteAdmin) {
            $validRoles->where(['perm_admin' => 0]);
        }
        $validRoles = $validRoles->all()->toArray();
        $conditions = [
            'disabled' => 0
        ];
        if (!$isSiteAdmin) {
            $conditions['OR'] = [
                ['role_id IN' => array_keys($validRoles)],
                ['id' => $currentUser['id']]
            ];
            if ($isGroupAdmin) {
                $OrgGroups = \Cake\ORM\TableRegistry::getTableLocator()->get('OrgGroups');
                $conditions['organisation_id IN'] = $OrgGroups->getGroupOrgIdsForUser($currentUser);
            } else {
                $conditions['organisation_id'] = $currentUser['organisation_id'];
            }
        }
        $validIndividualIds = $this->Users->find()->select(['individual_id'])->where($conditions)->all()->extract('individual_id')->toArray();
        return $validIndividualIds;
    }

    public function getAllOrganisations($currentUser): array
    {
        $this->Organisations = \Cake\ORM\TableRegistry::getTableLocator()->get('Organisations');
        $orgs = $this->Organisations->find()->select(['id', 'name'])->all()->toList();
        return Hash::combine($orgs, '{n}.id', '{n}.name');
    }
}
