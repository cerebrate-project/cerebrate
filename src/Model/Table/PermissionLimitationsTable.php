<?php

namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Error\Debugger;
use Cake\ORM\TableRegistry;

class PermissionLimitationsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('AuditLog');
        $this->setDisplayField('permission');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->notEmptyString('permission')
            ->notEmptyString('scope')
            ->naturalNumber('max_occurrence', __('That field can only hold non-negative values.'))
            ->requirePresence(['permission', 'scope', 'max_occurrence'], 'create');
        return $validator;
    }

    public function getListOfLimitations(\App\Model\Entity\User $data)
    {
        $Users = TableRegistry::getTableLocator()->get('Users');
        $ownOrgUserIds = $Users->find('list', [
            'keyField' => 'id',
            'valueField' => 'id',
            'conditions' => [
                'organisation_id' => $data['organisation_id']
            ]
        ])->all()->toList();
        $MetaFields = TableRegistry::getTableLocator()->get('MetaFields');
        $raw = $this->find()->select(['scope', 'permission', 'max_occurrence'])->disableHydration()->toArray();
        $limitations = [];
        foreach ($raw as $entry) {
            $limitations[$entry['permission']][$entry['scope']] = [
                'limit' => $entry['max_occurrence']
            ];
        }
        foreach ($limitations as $i => $permissions) { // Make sure global and organisations permission are mirror in the case where one of the two is not defined
            if (!isset($permissions['global']['limit'])) {
                $limitations[$i]['global']['limit'] = $permissions['organisation']['limit'];
            }
            if (!isset($permissions['organisation']['limit'])) {
                $limitations[$i]['organisation']['limit'] = $permissions['global']['limit'];
            }
        }
        foreach ($limitations as $field => $data) {
            if (isset($data['global'])) {
                $limitations[$field]['global']['current'] = $MetaFields->find('all', [
                    'conditions' => [
                        'scope' => 'user',
                        'field' => $field
                    ]
                ])->count();
            }
            if (isset($data['global'])) {
                $conditions = [
                    'scope' => 'user',
                    'field' => $field,
                ];
                if (!empty($ownOrgUserIds)) {
                    $conditions['parent_id IN'] = array_values($ownOrgUserIds);
                }
                $limitations[$field]['organisation']['current'] = $MetaFields->find('all', [
                    'conditions' => $conditions,
                ])->count();
            }
        }
        return $limitations;
    }

    public function attachLimitations(\App\Model\Entity\User $data)
    {
        $permissionLimitations = $this->getListOfLimitations($data);
        $icons = [
            'global' => 'globe',
            'organisation' => 'sitemap'

        ];
        if (!empty($data['MetaTemplates'])) {
            foreach ($data['MetaTemplates'] as &$metaTemplate) {
                foreach ($metaTemplate['meta_template_fields'] as &$meta_template_field) {
                    $boolean = $meta_template_field['type'] === 'boolean';
                    foreach ($meta_template_field['metaFields'] as &$metaField) {
                        if (isset($permissionLimitations[$metaField['field']])) {
                            foreach ($permissionLimitations[$metaField['field']] as $scope => $value) {
                                $messageType = 'warning';
                                if ($value['limit'] > $value['current']) {
                                    $messageType = 'info';
                                }
                                if ($value['limit'] < $value['current']) {
                                    $messageType = 'danger';
                                }
                                if (empty($metaField[$messageType])) {
                                    $metaField[$messageType] = '';
                                }
                                $altText = __(
                                    'There is a limitation enforced on the number of users with this permission {0}. Currently {1} slot(s) are used up of a maximum of {2} slot(s).',
                                    $scope === 'global' ? __('instance wide') : __('for your organisation'),
                                    $value['current'],
                                    $value['limit']
                                );
                                $metaField[$messageType] .= sprintf(
                                    ' <span title="%s"><span class="text-dark"><i class="fas fa-%s"></i>: </span>%s/%s</span>',
                                    $altText,
                                    $icons[$scope],
                                    $value['current'],
                                    $value['limit']
                                );
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }
}
