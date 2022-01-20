<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Query;

class AppTable extends Table
{
    public function initialize(array $config): void
    {
    }

    public function getStatisticsUsageForModel(Object $table, array $scopes, array $options=[]): array
    {
        $defaultOptions = [
            'limit' => 5,
            'includeOthers' => true,
            'ignoreNull' => true,
        ];
        $options = $this->getOptions($defaultOptions, $options);
        $stats = [];
        foreach ($scopes as $scope) {
            $queryTopUsage = $table->find();
            $queryTopUsage
                ->select([
                    $scope,
                    'count' => $queryTopUsage->func()->count('id'),
                ]);
            if ($queryTopUsage->getDefaultTypes()[$scope] != 'boolean') {
                $queryTopUsage->where(function (QueryExpression $exp) use ($scope) {
                    return $exp
                        ->isNotNull($scope)
                        ->notEq($scope, '');
                });
            }
            $queryTopUsage
                ->group($scope)
                ->order(['count' => 'DESC'])
                ->limit($options['limit'])
                ->page(1)
                ->enableHydration(false);
            $topUsage = $queryTopUsage->toList();
            $stats[$scope] = $topUsage;
            if (
                !empty($options['includeOthers']) && !empty($topUsage) &&
                $queryTopUsage->getDefaultTypes()[$scope] != 'boolean'  // No need to get others as we only have 2 possibilities already considered
            ) {
                $queryOthersUsage = $table->find();
                $queryOthersUsage
                    ->select([
                        'count' => $queryOthersUsage->func()->count('id'),
                    ])
                    ->where(function (QueryExpression $exp, Query $query) use ($topUsage, $scope, $options) {
                        if (!empty($options['ignoreNull'])) {
                            return $exp
                                ->isNotNull($scope)
                                ->notEq($scope, '')
                                ->notIn($scope, Hash::extract($topUsage, "{n}.{$scope}"));
                        } else {
                            return $exp->or([
                                $query->newExpr()->isNull($scope),
                                $query->newExpr()->eq($scope, ''),
                                $query->newExpr()->notIn($scope, Hash::extract($topUsage, "{n}.{$scope}")),
                            ]);
                        }
                    })
                    ->enableHydration(false);
                $othersUsage = $queryOthersUsage->toList();
                if (!empty($othersUsage)) {
                    $stats[$scope][] = [
                        $scope => __('Others'),
                        'count' => $othersUsage[0]['count'],
                    ];
                }
            }
        }
        return $stats;
    }

    private function getOptions($defaults=[], $options=[]): array
    {
        return array_merge($defaults, $options);
    }

    // Move this into a tool
    public function getActivityStatisticsForModel(Object $table, int $days = 30): array
    {
        $statistics = [];
        if ($table->hasBehavior('Timestamp')) {
            if ($table->getSchema()->getColumnType('created') == 'datetime') {
                $statistics['created'] = $this->getActivityStatistic($table, $days, 'created');
            }
            if ($table->getSchema()->getColumnType('modified') == 'datetime') {
                $statistics['modified'] = $this->getActivityStatistic($table, $days, 'modified');
            }
        }
        return $statistics;
    }

    public function getActivityStatistic(Object $table, int $days = 30, string $field = 'modified', bool $includeTimeline = true): array
    {
        $statistics = [];
        $statistics['days'] = $days;
        $statistics['amount'] = $table->find()->all()->count();
        if ($table->behaviors()->has('Timestamp') && $includeTimeline) {
            $statistics['timeline'] = $this->buildTimeline($table, $days, $field);
            $statistics['variation'] = $table->find()->where(["{$field} >" => new \DateTime("-{$days} days")])->all()->count();
        } else {
            $statistics['timeline'] = [];
            $statistics['variation'] = 0;
        }
        return $statistics;
    }

    public function buildTimeline(Object $table, int $days = 30, string $field = 'modified'): array
    {
        $timeline = [];
        $authorizedFields = ['modified', 'created'];
        if ($table->behaviors()->has('Timestamp')) {
            if (!in_array($field, $authorizedFields)) {
                throw new MethodNotAllowedException(__('Cannot construct timeline for field `{0}`', $field));
            }
            $days = $days - 1;
            $query = $table->find();
            $query->select([
                'count' => $query->func()->count('id'),
                'date' => "DATE({$field})",
            ])
                ->where(["{$field} >" => new \DateTime("-{$days} days")])
                ->group(['date'])
                ->order(['date']);
            $data = $query->all()->toArray();
            $interval = new \DateInterval('P1D');
            $period = new \DatePeriod(new \DateTime("-{$days} days"), $interval, (new \DateTime())->modify( '+1 day' ));
            foreach ($period as $date) {
                $timeline[$date->format("Y-m-d")] = [
                    'time' => $date->format("Y-m-d"),
                    'count' => 0
                ];
            }
            foreach ($data as $entry) {
                $timeline[$entry->date]['count'] = $entry->count;
            }
            $timeline = array_values($timeline);
        }
        return $timeline;
    }

    public function saveMetaFields($id, $input)
    {
        $this->MetaFields = TableRegistry::getTableLocator()->get('MetaFields');
        $this->MetaTemplates = TableRegistry::getTableLocator()->get('MetaTemplates');
        foreach ($input['metaFields'] as $templateID => $metaFields) {
            $metaTemplates = $this->MetaTemplates->find()->where([
                'id' => $templateID,
                'enabled' => 1
            ])->contain(['MetaTemplateFields'])->first();
            $fieldNameToId = [];
            foreach ($metaTemplates->meta_template_fields as $i => $metaTemplateField) {
                $fieldNameToId[$metaTemplateField->field] = $metaTemplateField->id;
            }
            foreach ($metaFields as $metaField => $values) {
                if (!is_array($values)) {
                    $values = [$values];
                }
                foreach ($values as $value) {
                    if ($value !== '') {
                        $temp = $this->MetaFields->newEmptyEntity();
                        $temp->field = $metaField;
                        $temp->value = $value;
                        $temp->scope = $this->metaFields;
                        $temp->parent_id = $id;
                        $temp->meta_template_id = $templateID;
                        $temp->meta_template_field_id = $fieldNameToId[$metaField];
                        $res = $this->MetaFields->save($temp);
                    }
                }
            }
        }
    }
}
